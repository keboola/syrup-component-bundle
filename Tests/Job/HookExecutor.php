<?php
namespace Syrup\ComponentBundle\Tests\Job;

use Syrup\ComponentBundle\Exception\JobException;
use Syrup\ComponentBundle\Job\HookExecutorInterface;
use Syrup\ComponentBundle\Job\Metadata\Job;
use Syrup\ComponentBundle\Job\Metadata\JobManager;

class HookExecutor extends \Syrup\ComponentBundle\Job\Executor implements HookExecutorInterface
{
	const HOOK_RESULT_KEY = 'postExecution';
	const HOOK_RESULT_VALUE = 'done';

	/**
	 * @var Job
	 */
	private $job;

	/**
	 * @var JobManager
	 */
	private $jobManager;

	/**
	 * @param JobManager $jobManager
	 */
	public function __construct(JobManager $jobManager)
	{
		$this->jobManager = $jobManager;
	}

	/**
	 * @param Job $job
	 * @return array
	 */
	public function execute(Job $job)
	{
		parent::execute($job);

		$this->job = $job;

		return array('testing' => 'HookExecutor');
	}

	/**
	 * Hook for modify job after job execution
	 *
	 * @param Job $job
	 * @return void
	 */
	public function postExecution(Job $job)
	{
		if ($job->getId() !== $this->job->getId()) {
			throw new \InvalidArgumentException('Given job must be same as previous executed');
		}

		if ($job->getComponent() !== $this->job->getComponent()) {
			throw new \InvalidArgumentException('Given job must be same as previous executed');
		}

		$job->setResult($job->getResult() + array(self::HOOK_RESULT_KEY => self::HOOK_RESULT_VALUE));

		$this->jobManager->updateJob($job);
	}
}