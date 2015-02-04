<?php
/**
 * @package syrup-component-bundle
 * @copyright 2015 Keboola
 * @author Jakub Matejka <jakub@keboola.com>
 */

namespace SyrupComponentBundle\Tests\Listener;


use Keboola\StorageApi\Client;
use Keboola\StorageApi\ClientException;
use Monolog\Handler\TestHandler;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Event\ConsoleExceptionEvent;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Syrup\ComponentBundle\Aws\S3\Uploader;
use Syrup\ComponentBundle\Command\JobCommand;
use Syrup\ComponentBundle\Exception\UserException;
use Syrup\ComponentBundle\Listener\SyrupExceptionListener;
use Syrup\ComponentBundle\Monolog\Formatter\JsonFormatter;
use Syrup\ComponentBundle\Monolog\Processor\LogProcessor;
use Syrup\ComponentBundle\Service\StorageApi\StorageApiService;

class SyrupExceptionListenerTest extends KernelTestCase
{
	/**
	 * @var TestHandler
	 */
	protected $testLogHandler;
	/**
	 * @var SyrupExceptionListener
	 */
	protected $listener;

	public function setUp()
	{
		$storageApiService = new StorageApiService();
		$storageApiService->setClient(new Client(['token' => SYRUP_SAPI_TEST_TOKEN]));
		$uploader = new Uploader([
			'aws-access-key' => SYRUP_AWS_KEY,
			'aws-secret-key' => SYRUP_AWS_SECRET,
			's3-upload-path' => SYRUP_S3_BUCKET
		]);
		$this->testLogHandler = new TestHandler();
		$this->testLogHandler->setFormatter(new JsonFormatter());
		$this->testLogHandler->pushProcessor(new LogProcessor(SYRUP_APP_NAME, $storageApiService, $uploader));
		$logger = new \Monolog\Logger('test', [$this->testLogHandler]);
		$this->listener = new SyrupExceptionListener(SYRUP_APP_NAME, $storageApiService, $logger);
	}

	/**
	 * @covers Syrup\ComponentBundle\Listener\SyrupExceptionListener::onConsoleException
	 */
	public function testConsoleException()
	{
		$command = new JobCommand();
		$input = new ArrayInput([]);
		$output = new NullOutput();

		$message = uniqid();
		$level = 500;
		$event = new ConsoleExceptionEvent($command, $input, $output, new \Exception($message), $level);
		$this->listener->onConsoleException($event);
		$records = $this->testLogHandler->getRecords();
		$this->assertCount(1, $records);
		$record = current($records);
		$this->assertArrayHasKey('message', $record);
		$this->assertEquals($message, $record['message']);
		$this->assertArrayHasKey('level', $record);
		$this->assertEquals($level, $record['level']);
		$this->assertArrayHasKey('exceptionId', $record);
		$this->assertArrayHasKey('exception', $record);
		$this->assertArrayHasKey('message', $record['exception']);
		$this->assertArrayHasKey('code', $record['exception']);
		$this->assertArrayHasKey('attachment', $record['exception']);
		$this->assertArrayHasKey('error', $record);
		$this->assertEquals('Application error', $record['error']);

		$message = uniqid();
		$level = 500;
		$event = new ConsoleExceptionEvent($command, $input, $output, new ClientException($message), $level);
		$this->listener->onConsoleException($event);
		$records = $this->testLogHandler->getRecords();
		$this->assertCount(2, $records);
		$record = array_pop($records);
		$this->assertArrayHasKey('exception', $record);
		$this->assertArrayHasKey('class', $record['exception']);
		$this->assertEquals('Keboola\StorageApi\ClientException', $record['exception']['class']);

		$message = uniqid();
		$level = 400;
		$event = new ConsoleExceptionEvent($command, $input, $output, new UserException($message), $level);
		$this->listener->onConsoleException($event);
		$records = $this->testLogHandler->getRecords();
		$this->assertCount(3, $records);
		$record = array_pop($records);
		$this->assertArrayHasKey('exception', $record);
		$this->assertArrayHasKey('class', $record['exception']);
		$this->assertEquals('Syrup\ComponentBundle\Exception\UserException', $record['exception']['class']);
		$this->assertArrayHasKey('error', $record);
		$this->assertEquals('User error', $record['error']);
	}

	/**
	 * @covers Syrup\ComponentBundle\Listener\SyrupExceptionListener::onKernelException
	 */
	public function testKernelException()
	{
		$request = Request::create('/syrup-component-bundle/run', 'POST');
		$request->headers->set('X-StorageApi-Token', SYRUP_SAPI_TEST_TOKEN);

		$message = uniqid();
		$event = new GetResponseForExceptionEvent(self::$kernel, $request, HttpKernelInterface::MASTER_REQUEST, new UserException($message));
		$this->listener->onKernelException($event);
		$records = $this->testLogHandler->getRecords();
		$this->assertCount(1, $records);
		$record = array_pop($records);
		$this->assertArrayHasKey('exception', $record);
		$this->assertArrayHasKey('class', $record['exception']);
		$this->assertEquals('Syrup\ComponentBundle\Exception\UserException', $record['exception']['class']);
		$this->assertArrayHasKey('error', $record);
		$this->assertEquals('User error', $record['error']);
		$response = $event->getResponse();
		$this->assertEquals(400, $response->getStatusCode());
		$jsonResponse = json_decode($response->getContent(), true);
		$this->assertArrayHasKey('status', $jsonResponse);
		$this->assertEquals('error', $jsonResponse['status']);
		$this->assertArrayHasKey('code', $jsonResponse);
		$this->assertEquals(400, $jsonResponse['code']);
		$this->assertArrayHasKey('exceptionId', $jsonResponse);
		$this->assertArrayHasKey('runId', $jsonResponse);

		$message = uniqid();
		$event = new GetResponseForExceptionEvent(self::$kernel, $request, HttpKernelInterface::MASTER_REQUEST, new ClientException($message));
		$this->listener->onKernelException($event);
		$records = $this->testLogHandler->getRecords();
		$this->assertCount(2, $records);
		$record = array_pop($records);
		$this->assertArrayHasKey('exception', $record);
		$this->assertArrayHasKey('class', $record['exception']);
		$this->assertEquals('Keboola\StorageApi\ClientException', $record['exception']['class']);
		$this->assertArrayHasKey('error', $record);
		$this->assertEquals('Application error', $record['error']);
		$response = $event->getResponse();
		$this->assertEquals(500, $response->getStatusCode());
		$jsonResponse = json_decode($response->getContent(), true);
		$this->assertArrayHasKey('status', $jsonResponse);
		$this->assertEquals('error', $jsonResponse['status']);
		$this->assertArrayHasKey('code', $jsonResponse);
		$this->assertEquals(500, $jsonResponse['code']);
		$this->assertArrayHasKey('exceptionId', $jsonResponse);
		$this->assertArrayHasKey('runId', $jsonResponse);

		$exception = new UserException(uniqid());
		$exception->setData(['d1' => uniqid(), 'd2' => uniqid()]);
		$event = new GetResponseForExceptionEvent(self::$kernel, $request, HttpKernelInterface::MASTER_REQUEST, $exception);
		$this->listener->onKernelException($event);
		$records = $this->testLogHandler->getRecords();
		$this->assertCount(3, $records);
		$record = array_pop($records);
		$this->assertArrayHasKey('context', $record);
		$this->assertArrayHasKey('data', $record['context']);
		$this->assertArrayHasKey('d1', $record['context']['data']);
		$this->assertArrayHasKey('d2', $record['context']['data']);
	}

}
