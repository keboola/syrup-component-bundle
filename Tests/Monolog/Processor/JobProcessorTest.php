<?php
/**
 * @package syrup-component-bundle
 * @copyright 2015 Keboola
 * @author Jakub Matejka <jakub@keboola.com>
 */

namespace SyrupComponentBundle\Tests\Monolog\Processor;

use Syrup\ComponentBundle\Aws\S3\Uploader;
use Symfony\Component\HttpFoundation\Request;
use Syrup\ComponentBundle\Job\Metadata\Job;
use Syrup\ComponentBundle\Monolog\Processor\JobProcessor;
use Syrup\ComponentBundle\Service\StorageApi\StorageApiService;
use Syrup\ComponentBundle\Tests\Monolog\TestCase;


class JobProcessorTest extends TestCase
{

	/**
	 * @covers Syrup\ComponentBundle\Monolog\Processor\JobProcessor::__invoke
	 * @covers Syrup\ComponentBundle\Monolog\Processor\JobProcessor::processRecord
	 * @covers Syrup\ComponentBundle\Monolog\Processor\JobProcessor::setJob
	 */
	public function testProcessor()
	{
		$processor = new JobProcessor();
		$processor->setJob(new Job([
			'id' => uniqid(),
			'runId' => uniqid(),
			'lockName' => uniqid()
		]));
		$record = $processor($this->getRecord());
		$this->assertArrayHasKey('job', $record);
		$this->assertArrayHasKey('id', $record['job']);
	}

}
