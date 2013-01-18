<?php

namespace Syrup\ComponentBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase,
	Symfony\Component\HttpFoundation\Response
;

class ApiControllerTest extends WebTestCase
{
	public function testRunAction()
	{
		$client = static::createClient();
		$sapiToken = static::$kernel->getContainer()->getParameter('storageApi.test.token');

		$client->request(
			'POST',
			'/ex-dummy/run',
			array(),
			array(),
			array(
				'CONTENT_TYPE'              => 'application/json',
				'HTTP_X-StorageApi-Token'   => $sapiToken
			)
		);

		/**
		 * @var Response $response
		 */
		$response = $client->getResponse();
		$content = json_decode($response->getContent(), true);

		$this->assertEquals('200', $response->getStatusCode());
		$this->assertEquals('ok', $content['status']);
	}

}
