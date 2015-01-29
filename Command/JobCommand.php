<?php
/**
 * Created by PhpStorm.
 * User: mirocillik
 * Date: 05/11/13
 * Time: 13:37
 */

namespace Syrup\ComponentBundle\Command;


use Doctrine\DBAL\Connection;
use Keboola\Encryption\EncryptorInterface;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Syrup\ComponentBundle\Exception\JobException;
use Syrup\ComponentBundle\Exception\SyrupExceptionInterface;
use Syrup\ComponentBundle\Exception\UserException;
use Keboola\StorageApi\Client as SapiClient;
use Syrup\ComponentBundle\Job\Exception\InitializationException;
use Syrup\ComponentBundle\Job\ExecutorInterface;
use Syrup\ComponentBundle\Job\Metadata\Job;
use Syrup\ComponentBundle\Job\Metadata\JobManager;
use Syrup\ComponentBundle\Monolog\Formatter\SyrupJsonFormatter;
use Syrup\ComponentBundle\Service\Db\Lock;

class JobCommand extends ContainerAwareCommand
{
	const STATUS_SUCCESS    = 0;
	const STATUS_ERROR      = 1;
	const STATUS_LOCK       = 64;
	const STATUS_RETRY      = 65;

	/** @var JobManager */
	protected $jobManager;

	/** @var Job */
	protected $job;

	/** @var SapiClient */
	protected $sapiClient;

	/** @var Logger */
	protected $logger;

	/** @var Lock */
	protected $lock;

	protected function configure()
	{
		$this
			->setName('syrup:run-job')
			->setDescription('Command to execute jobs')
			->addArgument('jobId', InputArgument::REQUIRED, 'ID of the job')
		;
	}

	protected function initialize(InputInterface $input, OutputInterface $output)
	{
		$jobId = $input->getArgument('jobId');

		if (is_null($jobId)) {
			throw new UserException("Missing jobId argument.");
		}

		$this->logger = $this->getContainer()->get('logger');

		try {
			// Get job from ES
			$this->job = $this->getJobManager()->getJob($jobId);

			if ($this->job == null) {
				$this->logger->error("Job id '".$jobId."' not found.");
				return self::STATUS_ERROR;
			}

			// SAPI init
			/** @var EncryptorInterface $encryptor */
			$encryptor = $this->getContainer()->get('syrup.encryptor');

			$this->sapiClient = new SapiClient([
				'token'     => $encryptor->decrypt($this->job->getToken()['token']),
				'url'       => $this->getContainer()->getParameter('storage_api.url'),
				'userAgent' => $this->job->getComponent(),
			]);
			$this->sapiClient->setRunId($this->job->getRunId());

			/** @var SyrupJsonFormatter $logFormatter */
			$logFormatter = $this->getContainer()->get('syrup.monolog.json_formatter');
			$logFormatter->setStorageApiClient($this->sapiClient);
			$logFormatter->setJob($this->job);

			// Lock DB
			/** @var Connection $conn */
			$conn = $this->getContainer()->get('doctrine.dbal.lock_connection');
			$conn->exec('SET wait_timeout = 31536000;');
			$this->lock = new Lock($conn, $this->job->getLockName());

		} catch (\Exception $e) {

			// Initialization error -> job will be requeued
			$this->logException('error', $e);

			// Don't update job status or result -> error could be related to ES
			// Don't unlock DB, error happend either before lock creation or when creating the lock, so the DB isn't locked

			return self::STATUS_RETRY;
		}
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		if (!$this->lock->lock()) {
			return self::STATUS_LOCK;
		}

		$startTime = time();

		// Update job status to 'processing'
		$this->job->setStatus(Job::STATUS_PROCESSING);
		$this->job->setStartTime(date('c', $startTime));
        $this->job->setEndTime(null);
        $this->job->setDurationSeconds(null);
        $this->job->setResult(null);
		$this->job->setProcess([
			'host'  => gethostname(),
			'pid'   => getmypid()
		]);

		$this->jobManager->updateJob($this->job);

		// Instantiate jobExecutor based on component name
		$jobExecutorName = str_replace('-', '_', $this->job->getComponent()) . '.job_executor';

		/** @var ExecutorInterface $jobExecutor */
		$jobExecutor = $this->getContainer()->get($jobExecutorName);
		$jobExecutor->setStorageApi($this->sapiClient);

		// Execute job
		try {
			$jobResult = $jobExecutor->execute($this->job);
			$jobStatus = Job::STATUS_SUCCESS;
			$status = self::STATUS_SUCCESS;
		} catch (InitializationException $e) {
			// job will be requeued
			$exceptionId = $this->logException('error', $e);
			$jobResult = [
				'message'       => $e->getMessage(),
				'exceptionId'   => $exceptionId
			];
			$jobStatus = Job::STATUS_PROCESSING;
			$status = self::STATUS_RETRY;

		} catch (UserException $e) {
			$exceptionId = $this->logException('error', $e);
			$jobResult = [
				'message'       => $e->getMessage(),
				'exceptionId'   => $exceptionId
			];
			$jobStatus = Job::STATUS_ERROR;
			$status = self::STATUS_SUCCESS;

		} catch (JobException $e) {
			$logLevel = 'error';
			if ($e->getStatus() === Job::STATUS_WARNING) {
				$logLevel = Job::STATUS_WARNING;
			}

			$exceptionId = $this->logException($logLevel, $e);
			$jobResult = [
				'message'       => $e->getMessage(),
				'exceptionId'   => $exceptionId
			];

			if ($e->getResult()) {
				$jobResult += $e->getResult();
			}

			$jobStatus = $e->getStatus();
			$status = self::STATUS_SUCCESS;
		} catch (\Exception $e) {
            // make sure that the job is recorded as failed
            $jobStatus = Job::STATUS_ERROR;
            $jobResult = [
                'message' => 'Internal error occurred, evaluating details'
            ];
            $this->job->setStatus($jobStatus);
            $this->job->setResult($jobResult);
            $this->jobManager->updateJob($this->job);

            // try to log the exception
			$exceptionId = $this->logException('critical', $e);
			$jobResult = [
				'message' => 'Internal error occurred, please contact support@keboola.com',
				'exceptionId'   => $exceptionId
			];
			$status = self::STATUS_ERROR;
		}

		// Update job with results
		$endTime = time();
		$duration = $endTime - $startTime;

		$this->job->setStatus($jobStatus);
		$this->job->setResult($jobResult);
		$this->job->setEndTime(date('c', $endTime));
		$this->job->setDurationSeconds($duration);
		$this->jobManager->updateJob($this->job);

		// DB unlock
		$this->lock->unlock();

		return $status;
	}

	/**
	 * @return JobManager
	 */
	protected function getJobManager()
	{
		if ($this->jobManager == null) {
			$this->jobManager = $this->getContainer()->get('syrup.job_manager');
		}

		return $this->jobManager;
	}

	protected function logException($level, \Exception $exception)
	{
		$component = 'unknown';
		if ($this->job != null) {
			$component = $this->job->getComponent();
		}

		$exceptionId = $component . '-' . md5(microtime());

		$logData = [
			'exceptionId'   => $exceptionId,
			'exception'     => $exception,
		];

		// SyrupExceptionInterface holds additional data
		if ($exception instanceof SyrupExceptionInterface) {
			$logData['data'] = $exception->getData();
		}

		$this->logger->$level($exception->getMessage(), $logData);

		return $exceptionId;
	}
}
