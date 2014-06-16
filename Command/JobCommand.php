<?php
/**
 * Created by PhpStorm.
 * User: mirocillik
 * Date: 05/11/13
 * Time: 13:37
 */

namespace Syrup\ComponentBundle\Command;


use Keboola\StorageApi\Client;
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
			return;
		}

		$this->sapiClient = new SapiClient([
			'token' => $this->job->getToken(),
			'url' => $this->getContainer()->getParameter('storage_api.url'),
			'userAgent' => $this->job->getComponent(),
		]);
		$this->sapiClient->setRunId($this->job->getRunId());
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

		// update job status to 'processing'
		$this->job->setStatus(Job::STATUS_PROCESSING);
		$this->jobManager->updateJob($this->job);

		$jobExecutorName = str_replace('-', '_', $this->job->getComponent()) . '.job_executor';

		/** @var ExecutorInterface $jobExecutor */
		$jobExecutor = $this->getContainer()->get($jobExecutorName);
		$jobExecutor->setStorageApi($this->sapiClient);

		try {
			// execute job
			$result = $jobExecutor->execute($this->job);
			$this->job->setStatus(Job::STATUS_SUCCESS);
			$this->job->setResult($result);
			$this->jobManager->updateJob($this->job);

			$lock->unlock();
			return self::STATUS_SUCCESS;
		} catch (UserException $e) {

			// update job with error message
			$this->job->setStatus(Job::STATUS_ERROR);
			$this->job->setResult($e->getMessage());
			$this->jobManager->updateJob($this->job);

			$lock->unlock();

			//@todo log exception

			return self::STATUS_SUCCESS;
		} catch (\Exception $e) {

			// update job with 'contact support' message
			$this->job->setStatus(Job::STATUS_ERROR);
			$this->job->setResult('Internal error occured please contact support@keboola.com');
			$this->jobManager->updateJob($this->job);

			$lock->unlock();

			//@todo log exception

			return self::STATUS_ERROR;
		}
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
