<?php
/**
 * Created by Miroslav Čillík <miro@keboola.com>
 * Date: 19/02/14
 * Time: 16:35
 */

namespace Syrup\ComponentBundle\Tests\Controller;


use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Component\HttpFoundation\Request;
use Syrup\ComponentBundle\Controller\BaseController;
use Syrup\ComponentBundle\Monolog\Formatter\SyrupJsonFormatter;
use Syrup\ComponentBundle\Test\WebTestCase;

class BaseControllerTest extends WebTestCase
{
	/** @var Client */
	static $client;

	/** @var BaseController */
	protected $baseController;

	protected $componentName = 'ex-dummy';

	public function setUp()
	{
		self::$client = static::createClient();

		$this->baseController = new BaseController();
		$this->baseController->setContainer(self::$client->getContainer());
	}

	public function testInitTempService()
	{
		$this->invokeMethod($this->baseController, 'initTempService', array('ex-dummy'));
		$tempService = self::$client->getContainer()->get('syrup.temp_service');
		$this->assertInstanceOf('Syrup\ComponentBundle\Filesystem\TempService', $tempService);
	}

	public function testInitLogger()
	{
		$this->invokeMethod($this->baseController, 'initLogger', array('ex-dummy'));

		/** @var SyrupJsonFormatter $formatter */
		$formatter = self::$client->getContainer()->get('syrup.monolog.json_formatter');
		$this->assertInstanceOf('Syrup\ComponentBundle\Monolog\Formatter\SyrupJsonFormatter', $formatter);
		$this->assertEquals($this->componentName, $formatter->getComponentName());

		$logger = self::$client->getContainer()->get('logger');
		$this->assertInstanceOf('Symfony\Bridge\Monolog\Logger', $logger);
	}

	public function testInitEncryptor()
	{
		$this->invokeMethod($this->baseController, 'initEncryptor', array('ex-dummy'));
		$encryptor = self::$client->getContainer()->get('syrup.encryptor');
		$this->assertInstanceOf('Syrup\ComponentBundle\Service\Encryption\Encryptor', $encryptor);
	}

	public function testPreExecute()
	{
		$request = Request::create('/ex-dummy/run', 'POST');
		$container = self::$client->getContainer();
		$container->enterScope('request');
		$container->set('request', $request);

		$this->baseController->setContainer($container);

		$this->baseController->preExecute($request);

		$logger = static::readAttribute($this->baseController, 'logger');
		$this->assertInstanceOf('Symfony\Bridge\Monolog\Logger', $logger);

		$temp = static::readAttribute($this->baseController, 'temp');
		$this->assertInstanceOf('Syrup\ComponentBundle\Filesystem\TempService', $temp);

		$componentName = static::readAttribute($this->baseController, 'componentName');
		$this->assertEquals($this->componentName, $componentName);
	}
}
