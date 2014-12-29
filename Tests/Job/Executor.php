<?php
namespace Syrup\ComponentBundle\Tests\Job;

use Syrup\ComponentBundle\Job\Metadata\Job;

class Executor extends \Syrup\ComponentBundle\Job\Executor
{
	public function execute(Job $job)
	{
		parent::execute($job);

		$job->setResult(array('testing' => 'value'));
		$job->setStatus(Job::STATUS_WARNING);

		return $job;
	}
}