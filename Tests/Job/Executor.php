<?php
namespace Syrup\ComponentBundle\Tests\Job;

use Syrup\ComponentBundle\Exception\JobWarningException;
use Syrup\ComponentBundle\Job\Metadata\Job;

class Executor extends \Syrup\ComponentBundle\Job\Executor
{
	public function execute(Job $job)
	{
		parent::execute($job);

		throw new JobWarningException(400, 'One of orchestration tasks failed', null, array('testing' => 'value'));
	}
}