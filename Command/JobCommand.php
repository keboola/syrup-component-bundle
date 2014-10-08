<?php
/**
 * Created by PhpStorm.
 * User: mirocillik
 * Date: 05/11/13
 * Time: 13:37
 */

namespace Syrup\ComponentBundle\Command;


use Keboola\Encryption\EncryptorInterface;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Syrup\ComponentBundle\Exception\SyrupComponentException;
use Syrup\ComponentBundle\Exception\UserException;
use Keboola\StorageApi\Client as SapiClient;
use Syrup\ComponentBundle\Job\Exception\InitializationException;
use Syrup\ComponentBundle\Job\ExecutorInterface;
use Syrup\ComponentBundle\Job\Metadata\Job;
use Syrup\ComponentBundle\Job\Metadata\JobManager;
use Syrup\ComponentBundle\Service\Db\Lock;

class JobCommand extends ContainerAwareCommand
{
	const STATUS_SUCCESS    = 0;
	const STATUS_ERROR      = 1;
	const STATUS_LOCK       = 2;
	const STATUS_RETRY      = 3;

	/** @var JobManager */
	protected $jobManager;

	/** @var Job */
	protected $job;

	/** @var SapiClient */
	protected $sapiClient;

	/** @var Logger */
	protected $logger;

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
		$this->logger = $this->getContainer()->get('logger');

		$jobId = $input->getArgument('jobId');

		if (is_null($jobId)) {
			throw new UserException("Missing jobId argument.");
		}

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
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		if ($this->job == null) {
			return self::STATUS_ERROR;
		}

		// Lock DB
		/** @var \PDO $pdo */
		$pdo = $this->getContainer()->get('pdo');
		$pdo->exec('SET wait_timeout = 31536000;');
		$lock = new Lock($pdo, $this->job->getLockName());

		if (!$lock->lock()) {
			return self::STATUS_LOCK;
		}

		$startTime = time();

		// Update job status to 'processing'
		$this->job->setStatus(Job::STATUS_PROCESSING);
		$this->job->setStartTime(date('c', $startTime));
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
			// job will be requeud
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

			$this->logException('error', $e);

		} catch (\Exception $e) {
			$exceptionId = $this->logException('critical', $e);
			$jobResult = [
				'message' => 'Internal error occured please contact support@keboola.com',
				'exceptionId'   => $exceptionId
			];
			$jobStatus = Job::STATUS_ERROR;
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
		$lock->unlock();

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
		$exceptionId = $this->job->getComponent() . '-' . md5(microtime());

		$this->logger->$level(
			$exception->getMessage(),
			[
				'exceptionId'   => $exceptionId,
				'exception'     => $exception,
				'job'           => $this->job->getLogData()
			]
		);

		return $exceptionId;
	}
}
