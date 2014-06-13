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

	public function setUp()
	{
		self::$client = static::createClient();

		$this->baseController = new BaseController();
		$this->baseController->setContainer(self::$client->getContainer());
	}

	public function testInitTempService()
	{
		$this->invokeMethod($this->baseController, 'initTemp');
		$tempService = self::$client->getContainer()->get('syrup.temp');
		$this->assertInstanceOf('Syrup\ComponentBundle\Filesystem\Temp', $tempService);
	}

	public function testInitLogger()
	{
		$this->invokeMethod($this->baseController, 'initLogger');

		/** @var SyrupJsonFormatter $formatter */
		$formatter = self::$client->getContainer()->get('syrup.monolog.json_formatter');
		$this->assertInstanceOf('Syrup\ComponentBundle\Monolog\Formatter\SyrupJsonFormatter', $formatter);
		$this->assertEquals(self::$client->getContainer()->getParameter('app_name'), $formatter->getAppName());

		$logger = self::$client->getContainer()->get('logger');
		$this->assertInstanceOf('Symfony\Bridge\Monolog\Logger', $logger);
	}
}
