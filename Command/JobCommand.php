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
use Syrup\ComponentBundle\Filesystem\TempService;
use Syrup\ComponentBundle\Job\Job;
use Syrup\ComponentBundle\Job\JobManager;
use Keboola\StorageApi\Client as SapiClient;
use Syrup\ComponentBundle\Service\Queue\QueueService;

abstract class JobCommand extends ContainerAwareCommand
{
	protected $componentName = '';

	/** @var JobManager */
	protected $jobManager;

	/** @var Job */
	protected $job;

	/** @var SapiClient */
	protected $sapiClient;

	/** @var TempService */
    protected $temp;

	/** @var  QueueService */
	protected $queue;

	protected function configure()
	{
		$this
			->addArgument(
				'jobId',
				InputArgument::REQUIRED,
				'ID of the jbo'
			)
		;
	}

	protected function initialize(InputInterface $input, OutputInterface $output)
	{
		$jobId = $input->getArgument('jobId');

		$this->job = $this->getJob($jobId);

		$this->sapiClient = new SapiClient([
			'token' => $this->job->getToken(),
			'url' => $this->getContainer()->getParameter('storage_api.url'),
			'userAgent' => $this->componentName,
		]);

		$this->sapiClient->setRunId($jobId);

		// update job status to 'processing'
		$this->job->setStatus(Job::STATUS_PROCESSING);
		$this->jobManager->updateJob($this->job);
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
		return new Job($this->getJobManager()->getJobData($jobId, $this->componentName));
	}

    protected function getTemp()
    {
        if ($this->temp == null) {
            $this->temp = $this->getContainer()->get('syrup.temp_factory')->get($this->componentName);
        }

        return $this->temp;
    }

	protected function getJobQueue()
	{
		if ($this->queue == null) {
			$this->queue = $this->getContainer()->get('syrup.job_queue');
		}

		return $this->queue;
	}
}
