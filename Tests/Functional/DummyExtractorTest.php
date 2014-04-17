<?php

namespace Syrup\ComponentBundle\Tests\Functional;

use Keboola\StorageApi\Client;
use Keboola\StorageApi\Table;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * ApiControllerTest.php
 *
 * @author: Miroslav Čillík <miro@keboola.com>
 * @created: 6.6.13
 */

class DummyExtractorTest extends WebTestCase
{
	const DATA_BUCKET_ID = 'in.c-ex-dummy';
	const DATA_BUCKET_NAME = 'ex-dummy';

	/**
	 * @var Client
	 */
	protected static $storageApi;

	/**
	 * @var \Symfony\Bundle\FrameworkBundle\Client
	 */
	protected static $client;

	public static function setUpBeforeClass()
	{
		self::$client = static::createClient();
		$container = self::$client->getContainer();
		self::$client->setServerParameters(array(
			'HTTP_X-StorageApi-Token' => $container->getParameter('storage_api.test.token')
		));

		self::$storageApi = new Client([
			'token' => $container->getParameter('storage_api.test.token'),
			'url' => self::$client->getContainer()->getParameter('storage_api.url'),
		]);

		// Clear test environment
		if (self::$storageApi->bucketExists(self::DATA_BUCKET_ID)) {
			$bucketInfo = self::$storageApi->getBucket(self::DATA_BUCKET_ID);
			foreach ($bucketInfo['tables'] as $table) {
				self::$storageApi->dropTable($table['id']);
			}
			self::$storageApi->dropBucket(self::DATA_BUCKET_ID);
		}
	}

	public function testDummyRun()
	{
		self::$storageApi->createBucket(self::DATA_BUCKET_NAME, 'in', 'Syrup Tests data bucket');
		self::$client->request('POST', '/ex-dummy/run');

		/** @var Response $response */
		$response = self::$client->getResponse();
		$content = json_decode($response->getContent(), true);

		$this->assertEquals($response->getStatusCode(), 200);
		$this->assertArrayHasKey('status', $content);
		$this->assertArrayHasKey('duration', $content);
		$this->assertEquals('ok', $content['status']);

		// Data uploaded?
		$data = array(
			array('1', 'a', 'b', 'c'),
			array('2', 'd', 'e', 'f'),
			array('3', 'g', 'h', 'i'),
			array('4', 'j', 'k', 'l'),
		);

		$result = self::$storageApi->exportTable(self::DATA_BUCKET_ID . '.dummy');

		$table = new Table(self::$storageApi, self::DATA_BUCKET_ID . '.dummy');
		$table->setFromString($result, ',', '"', true);

		$this->assertEquals($data, $table->getData());
	}

	public function testDummyRunExceptions()
	{
		//@todo
	}

}
