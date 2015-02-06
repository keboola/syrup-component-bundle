<?php

namespace Syrup\ComponentBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Component\HttpFoundation\Request;
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
    private static $client;

    /** @var ApiController */
    protected $controller;

    protected $container;

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

        $this->container = $container;
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

    public function testNonExistingBundle()
    {
        static::$client->request(
            'GET',
            '/' . uniqid(),
            [],
            [],
            ['HTTP_X-StorageApi_Token' => $this->container->getParameter('storage_api.test.token')],
            '{"test":"test"}'
        );

        $result = json_decode(static::$client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('status', $result);
        $this->assertEquals('error', $result['status']);
        $this->assertArrayHasKey('error', $result);
        //@TODO $this->assertEquals('User error', $result['error']);
        $this->assertArrayHasKey('code', $result);
        //@TODO $this->assertEquals(404, $result['code']);
    }

    public function testNonExistingAction()
    {
        static::$client->request(
            'POST',
            '/syrup-component-bundle/' . uniqid(),
            [],
            [],
            ['HTTP_X-StorageApi_Token' => $this->container->getParameter('storage_api.test.token')],
            '{"test":"test"}'
        );

        $result = json_decode(static::$client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('status', $result);
        $this->assertEquals('error', $result['status']);
        $this->assertArrayHasKey('code', $result);
        $this->assertEquals(405, $result['code']);
    }

    public function testRunActionWithoutToken()
    {
        $clientWithoutToken = static::createClient();
        $clientWithoutToken->request(
            'POST',
            '/syrup-component-bundle/run',
            [],
            [],
            [],
            '{"test":"test"}'
        );

        $result = json_decode($clientWithoutToken->getResponse()->getContent(), true);
        $this->assertArrayHasKey('status', $result);
        $this->assertEquals('error', $result['status']);
        $this->assertArrayHasKey('error', $result);
        $this->assertEquals('User error', $result['error']);
        $this->assertArrayHasKey('code', $result);
        $this->assertEquals(400, $result['code']);
    }

    // basic RunAction test
    public function testRunAction()
    {
        static::$client->request(
            'POST',
            '/syrup-component-bundle/run',
            [],
            [],
            ['HTTP_X-StorageApi_Token' => $this->container->getParameter('storage_api.test.token')],
            '{"test":"test"}'
        );

        $res = json_decode(static::$client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('id', $res);
        $this->assertArrayHasKey('url', $res);
        $this->assertArrayHasKey('status', $res);
    }

    // test wrong parameter user error
    public function testRunActionWrongParams()
    {
        try {
            static::$client->request(
                'POST',
                '/syrup-component-bundle/run',
                [],
                [],
                ['HTTP_X-StorageApi_Token' => $this->container->getParameter('storage_api.test.token')],
                '{"bull":"crap"}'
            );
        } catch (\Exception $e) {
            print $e->getTraceAsString();
            die;
        }

        $result = json_decode(static::$client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('status', $result);
        $this->assertEquals('error', $result['status']);
        $this->assertArrayHasKey('error', $result);
        $this->assertEquals('User error', $result['error']);
        $this->assertArrayHasKey('code', $result);
        $this->assertEquals(400, $result['code']);
        $this->assertArrayHasKey('exceptionId', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('runId', $result);
    }
}
