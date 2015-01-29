<?php
namespace Syrup\ComponentBundle\Tests\Job;

use Syrup\ComponentBundle\Exception\JobException;
use Syrup\ComponentBundle\Job\Metadata\Job;

class SuccessExecutor extends \Syrup\ComponentBundle\Job\Executor
{
	public function execute(Job $job)
	{
		parent::execute($job);

		$e = new JobException(200, 'All done');
		$e
			->setStatus(Job::STATUS_SUCCESS)
			->setResult(array('testing' => 'value'));

		throw $e;
	}
}