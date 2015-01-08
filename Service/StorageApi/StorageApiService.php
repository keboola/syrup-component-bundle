<?php
/**
 * Created by Miroslav Čillík <miro@keboola.com>
 * Date: 06/12/13
 * Time: 16:29
 */

namespace Syrup\ComponentBundle\Service\StorageApi;


use Keboola\StorageApi\Client;
use Symfony\Component\HttpFoundation\Request;
use Syrup\ComponentBundle\Exception\NoRequestException;
use Syrup\ComponentBundle\Exception\UserException;

class StorageApiService
{
	/** @var Request */
	protected $request;

	/** @var Client */
	protected $client;

	protected $storageApiUrl;

	public function __construct($storageApiUrl)
	{
		$this->storageApiUrl = $storageApiUrl;
	}

	public function setRequest($request = null)
	{
		$this->request = $request;
	}

	public function getClient()
	{
		if ($this->client == null) {
			if ($this->request == null) {
				throw new NoRequestException();
			}

			if (!$this->request->headers->has('X-StorageApi-Token')) {
				throw new UserException('Missing StorageAPI token');
			}

			if ($this->request->headers->has('X-StorageApi-Url')) {
				$this->storageApiUrl = $this->request->headers->get('X-StorageApi-Url');
			}

			$this->client = new Client([
				'token' => $this->request->headers->get('X-StorageApi-Token'),
				'url' => $this->storageApiUrl,
				'userAgent' => explode('/', $this->request->getPathInfo())[1],
			]);

			$this->client->verifyToken();

			if ($this->request->headers->has('X-KBC-RunId')) {
				$kbcRunId = $this->client->generateRunId($this->request->headers->get('X-KBC-RunId'));
			} else {
				$kbcRunId = $this->client->generateRunId();
			}

			$this->client->setRunId($kbcRunId);
		}

		return $this->client;
	}
}
