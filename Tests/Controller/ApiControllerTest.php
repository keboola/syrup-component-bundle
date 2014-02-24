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
		$container = self::$client->getContainer();

		$request = Request::create('/ex-dummy/run', 'POST');
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

	public function testInitComponent()
	{
		$this->invokeMethod($this->controller, 'initStorageApi');
		$sapiClient = static::readAttribute($this->controller, 'storageApi');
		$this->invokeMethod($this->controller, 'initComponent', array($sapiClient, $this->componentName));
		$component = static::readAttribute($this->controller, 'component');

		$this->assertInstanceOf('Syrup\ComponentBundle\Component\DummyExtractor', $component);
	}

	public function testGetSharedSapi()
	{
		$sharedSapi = $this->invokeMethod($this->controller, 'getSharedSapi');

		$this->assertInstanceOf('Syrup\ComponentBundle\Service\SharedSapi\SharedSapiService', $sharedSapi);
	}
}
