<?php
namespace Syrup\ComponentBundle\Tests\Job;

use Syrup\ComponentBundle\Exception\JobException;
use Syrup\ComponentBundle\Job\Metadata\Job;

class ErrorExecutor extends \Syrup\ComponentBundle\Job\Executor
{
    public function execute(Job $job)
    {
        parent::execute($job);

        $e = new JobException(500, 'One of orchestration tasks failed');
        $e
            ->setStatus(Job::STATUS_ERROR)
            ->setResult(array('testing' => 'value'));

        throw $e;
    }
}
