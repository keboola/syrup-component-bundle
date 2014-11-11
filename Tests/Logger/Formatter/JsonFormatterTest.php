<?php
/**
 * Created by Miroslav Čillík <miro@keboola.com>
 * Date: 20/02/14
 * Time: 16:39
 */

namespace Syrup\Logger\Formatter;


use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Syrup\ComponentBundle\Exception\ApplicationException;
use Syrup\ComponentBundle\Exception\UserException;
use Syrup\ComponentBundle\Job\Metadata\Job;
use Syrup\ComponentBundle\Monolog\Formatter\SyrupJsonFormatter;

class JsonFormatterTest extends WebTestCase
{
	protected $appName = 'syrup-component-bundle';

	protected function assertRecord(array $record)
	{
		$this->assertArrayHasKey('message', $record);
		$this->assertArrayHasKey('level', $record);
		$this->assertArrayHasKey('channel', $record);
		$this->assertArrayHasKey('context', $record);
		$this->assertArrayHasKey('component', $record);
		$this->assertArrayHasKey('priority', $record);
		$this->assertArrayHasKey('pid', $record);
		$this->assertArrayHasKey('runId', $record);
		$this->assertArrayHasKey('error', $record);
		$this->assertArrayHasKey('attachment', $record);

		$this->assertEquals($this->appName, $record['component']);
	}

	public function testFormatter()
	{
		$attachmentUrl = 'http://neco';
		$s3uploader = $this->getMockBuilder('Syrup\ComponentBundle\Monolog\Uploader\SyrupS3Uploader')
			->disableOriginalConstructor()
			->getMock();

		$s3uploader->expects($this->any())
			->method('uploadString')
			->will($this->returnValue($attachmentUrl));

		$storageApiService = $this->getMockBuilder('Syrup\ComponentBundle\Service\StorageApi\StorageApiService')
			->disableOriginalConstructor()
			->getMock();


		$storageApiService
			->expects($this->any())
			->method('getClient');

		$formatter = new SyrupJsonFormatter(
			$this->appName,
			$s3uploader,
			$storageApiService
		);

		// test job
		$formatter->setJob(new Job([
			'id'    => 1234,
			'runId'     => 1235,
			'project'   => [
				'id'        => 123,
				'name'      => 'test'
			],
			'token'     => [
				'id'            => 12345,
				'description'   => 'test',
				'token'         => '12345-test-token'
			],
			'component' => 'syrup-component-bundle',
			'command'   => 'test',
			'params'    => [],
			'process'   => [
				'host'  => gethostname(),
				'pid'   => getmypid()
			],
			'createdTime'   => date('c')
		]));

		$record = json_decode($formatter->format($this->createUserExceptionRecord()), true);

		$this->assertRecord($record);
		$this->assertEquals(400, $record['level']);
		$this->assertEquals('ERROR', $record['priority']);
		$this->assertEquals('User error', $record['error']);
		$this->assertNotEmpty($record['attachment']);
		$this->assertNotEmpty($record['job']);
		$this->assertEquals(1234, $record['job']['id']);
		$this->assertEquals(1235, $record['job']['runId']);

		$record = json_decode($formatter->format($this->createAppExceptionRecord()), true);

		$this->assertRecord($record);
		$this->assertEquals(500, $record['level']);
		$this->assertEquals('CRITICAL', $record['priority']);
		$this->assertEquals('Application error', $record['error']);
		$this->assertNotEmpty($record['attachment']);
		$this->assertNotEmpty($record['job']);
		$this->assertEquals(1234, $record['job']['id']);
		$this->assertEquals(1235, $record['job']['runId']);
	}

	public function createUserExceptionRecord()
	{
		return array(
			'message'       => 'User Exception Logging test',
			'level_name'    => 'ERROR',
			'level'         => 400,
			'channel'       => 'app',
			'context'       => array(
				'exception' => new UserException("Test User Exception")
			)
		);
	}

	public function createAppExceptionRecord()
	{
		return array(
			'message'       => 'Application Exception Logging test',
			'level_name'    => 'CRITICAL',
			'level'         => 500,
			'channel'       => 'app',
			'context'       => array(
				'exception' => new ApplicationException("Test Application Exception")
			)
		);
	}

}
