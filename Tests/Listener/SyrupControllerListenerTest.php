<?php
/**
 * @package syrup-component-bundle
 * @copyright 2015 Keboola
 * @author Jakub Matejka <jakub@keboola.com>
 */

namespace SyrupComponentBundle\Tests\Listener;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Syrup\ComponentBundle\Controller\ApiController;
use Syrup\ComponentBundle\Listener\SyrupControllerListener;

class SyrupControllerListenerTest extends WebTestCase
{

    /**
     * @covers Syrup\ComponentBundle\Listener\SyrupControllerListener::onKernelController
     */
    public function testListener()
    {
        $client = static::createClient();

        $request = Request::create('/syrup-component-bundle/run', 'POST');
        $request->headers->set('X-StorageApi-Token', SYRUP_SAPI_TEST_TOKEN);
        $client->getContainer()->enterScope('request');
        $client->getContainer()->set('request', $request);

        $controller = new ApiController();
        $controller->setContainer($client->getContainer());
        $event = new FilterControllerEvent(self::$kernel, [$controller, 'runAction'], $request, HttpKernelInterface::MASTER_REQUEST);

        $this->assertEmpty(\PHPUnit_Framework_Assert::readAttribute($controller, 'componentName'));
        $listener = new SyrupControllerListener();
        $listener->onKernelController($event);
        $this->assertNotEmpty(\PHPUnit_Framework_Assert::readAttribute($controller, 'componentName'));
    }
}
