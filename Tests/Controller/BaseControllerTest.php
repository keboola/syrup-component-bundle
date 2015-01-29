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
    /**
     * @var BaseController
     */
    private $controller;

    public function setUp()
    {
        $client = static::createClient();

        $this->controller = new BaseController();
        $this->controller->setContainer($client->getContainer());
    }

    public function testInitTemp()
    {
        $this->invokeMethod($this->controller, 'initTemp');
        $temp = static::readAttribute($this->controller, 'temp');
        $this->assertInstanceOf('Syrup\ComponentBundle\Filesystem\Temp', $temp);
        /** @var \Syrup\ComponentBundle\Filesystem\Temp $temp */
        $this->assertStringStartsWith(sys_get_temp_dir(), $temp->getTmpFolder());
    }

    public function testInitLogger()
    {
        $this->invokeMethod($this->controller, 'initLogger');
        $logger = static::readAttribute($this->controller, 'logger');
        $this->assertTrue(is_subclass_of($logger, 'Monolog\Logger'));
        /** @var \Monolog\Logger $logger */
        $this->assertTrue($logger->addDebug('Test'));
    }


    public function testCreateResponse()
    {
        $controller = new BaseController();

        $result = uniqid();
        $code = 202;
        $headers = array('three' => uniqid());

        $response = $controller->createResponse($result, $code, $headers);
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);
        $this->assertEquals($code, $response->getStatusCode());
        $this->assertEquals($result, $response->getContent());
        $responseHeaders = $response->headers->all();
        $this->assertArrayHasKey('three', $responseHeaders);
        $this->assertEquals(array($headers['three']), $responseHeaders['three']);
        $this->assertArrayHasKey('access-control-allow-origin', $responseHeaders);
        $this->assertEquals(array('*'), $responseHeaders['access-control-allow-origin']);
        $this->assertArrayHasKey('access-control-allow-methods', $responseHeaders);
        $this->assertEquals(array('*'), $responseHeaders['access-control-allow-methods']);
        $this->assertArrayHasKey('access-control-allow-headers', $responseHeaders);
        $this->assertEquals(array('*'), $responseHeaders['access-control-allow-headers']);
    }

    public function testCreateJsonResponse()
	{
        $controller = new BaseController();

        $result = array('one' => uniqid(), 'two' => uniqid());
        $code = 202;
        $headers = array('three' => uniqid());

        $response = $controller->createJsonResponse($result, $code, $headers);
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\JsonResponse', $response);
        $this->assertEquals($code, $response->getStatusCode());
        $this->assertEquals(json_encode($result), $response->getContent());
        $responseHeaders = $response->headers->all();
        $this->assertArrayHasKey('three', $responseHeaders);
        $this->assertEquals(array($headers['three']), $responseHeaders['three']);
        $this->assertArrayHasKey('content-type', $responseHeaders);
        $this->assertEquals(array('application/json'), $responseHeaders['content-type']);
        $this->assertArrayHasKey('access-control-allow-origin', $responseHeaders);
        $this->assertEquals(array('*'), $responseHeaders['access-control-allow-origin']);
        $this->assertArrayHasKey('access-control-allow-methods', $responseHeaders);
        $this->assertEquals(array('*'), $responseHeaders['access-control-allow-methods']);
        $this->assertArrayHasKey('access-control-allow-headers', $responseHeaders);
        $this->assertEquals(array('*'), $responseHeaders['access-control-allow-headers']);
	}
}
