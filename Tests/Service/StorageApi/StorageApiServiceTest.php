<?php
/**
 * Created by Miroslav Čillík <miro@keboola.com>
 * Date: 20/02/14
 * Time: 15:51
 */

namespace Syrup\StorageApi;


use Keboola\StorageApi\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Syrup\ComponentBundle\Service\StorageApi\StorageApiService;

class StorageApiServiceTest extends WebTestCase
{
	public function testStorageApiService()
	{
		$client = static::createClient();
		$container = $client->getContainer();

		$request = Request::create('/ex-dummy/run', 'POST');
		$request->headers->set('X-StorageApi-Token', $container->getParameter('storage_api.test.token'));
		$container->set('request', $request );

		/** @var StorageApiService $storageApiService */
		$storageApiService = $container->get('storage_api');

		$sapiClient = $storageApiService->getClient();

		$this->assertNotNull($sapiClient);
		$this->assertInstanceOf('Keboola\StorageApi\Client', $sapiClient);
	}

}
