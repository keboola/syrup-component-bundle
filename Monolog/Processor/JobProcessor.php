<?php
/**
 * @package syrup-component-bundle
 * @copyright 2015 Keboola
 * @author Jakub Matejka <jakub@keboola.com>
 */

namespace Syrup\ComponentBundle\Monolog\Processor;

use Syrup\ComponentBundle\Job\Metadata\JobInterface;

class JobProcessor
{
    /**
     * @var JobInterface
     */
    private $job;

    public function setJob(JobInterface $job)
    {
        $this->job = $job;
    }

    /**
     * @param  array $record
     * @return array
     */
    public function __invoke(array $record)
    {
        return $this->processRecord($record);
    }

    public function processRecord(array $record)
    {
        if ($this->job) {
            $record['job'] = $this->job->getLogData();
        }
        return $record;
    }
}
