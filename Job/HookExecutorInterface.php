<?php
namespace Syrup\ComponentBundle\Job;

use Syrup\ComponentBundle\Job\Metadata\Job;

interface HookExecutorInterface extends ExecutorInterface
{
	/**
	 * @param Job $job
	 * @return void
	 */
	public function postExecution(Job $job);
}