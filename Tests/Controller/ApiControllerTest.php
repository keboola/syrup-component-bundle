<?php

namespace Syrup\ComponentBundle\Tests\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Client;
use Syrup\ComponentBundle\Controller\ApiController;
use Syrup\ComponentBundle\Test\WebTestCase;

/**
 * ApiControllerTest.php
 *
 * @author: Miroslav Čillík <miro@keboola.com>
 * @created: 6.6.13
 */

class ApiControllerTest extends WebTestCase
{
	/** @var Client */
	static $client;

	/** @var ApiController */
	protected $controller;

	protected $componentName = 'ex-dummy';

	public function setUp()
	{
		self::$client = static::createClient();
		$container = static::$client->getContainer();

		$request = Request::create('/syrup-component-bundle/run', 'POST');
		$request->headers->set('X-StorageApi-Token', $container->getParameter('storage_api.test.token'));

		$container->enterScope('request');
		$container->set('request', $request);

		$this->controller = new ApiController();
		$this->controller->setContainer($container);
	}

	public function testInitStorageApi()
	{
		$this->invokeMethod($this->controller, 'initStorageApi');
		$sapiClient = static::readAttribute($this->controller, 'storageApi');
		$this->assertInstanceOf('Keboola\StorageApi\Client', $sapiClient);
	}

	public function testGetSharedSapi()
	{
		$sharedSapi = $this->invokeMethod($this->controller, 'getSharedSapi');

		$this->assertInstanceOf('Syrup\ComponentBundle\Service\SharedSapi\SharedSapiService', $sharedSapi);
	}

	public function testRun()
	{
		$container = static::$client->getContainer();

		static::$client->request(
			'POST',
			'/syrup-component-bundle/run',
			array(),
			array(),
			array(
				'HTTP_X-StorageApi-Token' => $container->getParameter('storage_api.test.token')
			)
		);

		$res = json_decode(static::$client->getResponse()->getContent(), true);

		$this->assertArrayHasKey('jobId', $res);
	}
}
