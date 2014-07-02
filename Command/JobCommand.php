<?php
/**
 * Created by PhpStorm.
 * User: mirocillik
 * Date: 05/11/13
 * Time: 13:37
 */

namespace Syrup\ComponentBundle\Command;


use Keboola\Encryption\EncryptorInterface;
use Keboola\StorageApi\Client;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Syrup\ComponentBundle\Exception\UserException;
use Keboola\StorageApi\Client as SapiClient;
use Syrup\ComponentBundle\Job\ExecutorInterface;
use Syrup\ComponentBundle\Job\Metadata\Job;
use Syrup\ComponentBundle\Job\Metadata\JobManager;
use Syrup\ComponentBundle\Service\Db\Lock;

class JobCommand extends ContainerAwareCommand
{
	const STATUS_SUCCESS = 0;
	const STATUS_ERROR = 1;
	const STATUS_LOCK = 2;

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
			->addArgument(
				'jobId',
				InputArgument::REQUIRED,
				'ID of the job'
			)
		;
	}

	protected function initialize(InputInterface $input, OutputInterface $output)
	{
		$jobId = $input->getArgument('jobId');

		if (is_null($jobId)) {
			throw new UserException("Missing jobId argument.");
		}

		$this->job = $this->getJob($jobId);

		if ($this->job == null) {
			return self::STATUS_ERROR;
		}

		/** @var EncryptorInterface $encryptor */
		$encryptor = $this->getContainer()->get('syrup.encryptor');

		$this->sapiClient = new SapiClient([
			'token'     => $encryptor->decrypt($this->job->getToken()['token']),
			'url'       => $this->getContainer()->getParameter('storage_api.url'),
			'userAgent' => $this->job->getComponent(),
		]);
		$this->sapiClient->setRunId($this->job->getRunId());

		$this->logger = $this->getContainer()->get('logger');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		if ($this->job == null) {
			return self::STATUS_ERROR;
		}

		/** @var \PDO $pdo */
		$pdo = $this->getContainer()->get('pdo');
		$pdo->exec('SET wait_timeout = 31536000;');
		$lock = new Lock($pdo, $this->job->getId());

		if (!$lock->lock()) {
			return self::STATUS_LOCK;
		}

		$startTime = time();

		// update job status to 'processing'
		$this->job->setStatus(Job::STATUS_PROCESSING);
		$this->job->setStartTime($startTime);
		$this->jobManager->updateJob($this->job);

		$jobExecutorName = str_replace('-', '_', $this->job->getComponent()) . '.job_executor';

		/** @var ExecutorInterface $jobExecutor */
		$jobExecutor = $this->getContainer()->get($jobExecutorName);
		$jobExecutor->setStorageApi($this->sapiClient);

		try {
			// execute job
			$result = $jobExecutor->execute($this->job);
			$status = Job::STATUS_SUCCESS;
		} catch (UserException $e) {

			// update job with error message
			$result = [
				'message' => $e->getMessage()
			];
			$status = Job::STATUS_ERROR;

			$this->logger->error(
				$e->getMessage(),
				[
					'exception' => $e,
					'job'       => $this->job->getLogData()
				]
			);
		} catch (\Exception $e) {

			// update job with 'contact support' message
			$result = [
				'message' => 'Internal error occured please contact support@keboola.com'
			];
			$status = Job::STATUS_ERROR;

			$this->logger->alert(
				$e->getMessage(),
				[
					'exception' => $e,
					'job'       => $this->job->getLogData()
				]
			);
		}

		$endTime = time();
		$duration = $endTime - $startTime;

		$this->job->setStatus($status);
		$this->job->setResult($result);
		$this->job->setEndTime($startTime);
		$this->job->setDurationSeconds($duration);
		$this->jobManager->updateJob($this->job);

		$lock->unlock();
		return $status;
	}

	protected function getJobManager()
	{
		if ($this->jobManager == null) {
			$this->jobManager = $this->getContainer()->get('syrup.job_manager');
		}

		return $this->jobManager;
	}

	protected function getJob($jobId)
	{
		return $this->getJobManager()->getJob($jobId);
	}
}
