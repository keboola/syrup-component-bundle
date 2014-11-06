<?php

namespace Syrup\ComponentBundle\Controller;

use Keboola\Encryption\EncryptorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Keboola\StorageApi\Client;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Keboola\StorageApi\Event as SapiEvent;
use Syrup\ComponentBundle\Component\Component;
use Syrup\ComponentBundle\Component\ComponentFactory;
use Syrup\ComponentBundle\Exception\ApplicationException;
use Syrup\ComponentBundle\Exception\UserException;
use Syrup\ComponentBundle\Filesystem\Temp;
use Syrup\ComponentBundle\Job\Metadata\Job;
use Syrup\ComponentBundle\Job\Metadata\JobInterface;
use Syrup\ComponentBundle\Job\Metadata\JobManager;
use Syrup\ComponentBundle\Service\Queue\QueueService;
use Syrup\ComponentBundle\Service\SharedSapi\jobEvent;
use Syrup\ComponentBundle\Service\SharedSapi\SharedSapiService;

class ApiController extends BaseController
{
	/** @var Client */
	protected $storageApi;

	protected function initStorageApi()
	{
		$this->storageApi = $this->container->get('storage_api')->getClient();
	}

	public function preExecute(Request $request)
	{
		parent::preExecute($request);

		$this->initStorageApi();
//		$this->initComponent($this->storageApi, $this->componentName);
	}

	/** @deprecated */
	protected function initComponent(Client $storageApi, $componentName)
	{
		/** @var ComponentFactory $componentFactory */
		$componentFactory = $this->container->get('syrup.component_factory');
		$this->component = $componentFactory->get($storageApi, $componentName);
		$this->component->setContainer($this->container);

		return $this->component;
	}

	/**
	 * Run Action
	 *
	 * Creates new job, saves it to Elasticsearch and add to SQS
	 *
	 * @param Request $request
	 * @return Response
	 */
	public function runAction(Request $request)
    {
	    // Get params from request
	    $params = $this->getPostJson($request);

	    // check params against ES mapping
	    $this->checkMappingParams($params);

	    // Create new job
	    /** @var Job $job */
	    $job = $this->createJob('run', $params);

	    // Add job to Elasticsearch
	    try {
		    $jobId = $this->getJobManager()->indexJob($job);
	    } catch (\Exception $e) {
		    throw new ApplicationException("Failed to create job", $e);
	    }

	    // Add job to SQS
	    $this->enqueue($jobId);

	    // Response with link to job resource
	    return $this->createJsonResponse([
		    'id'        => $jobId,
		    'url'       => $this->getJobUrl($jobId),
		    'status'    => $job->getStatus()
	    ], 202);
    }

	public function optionsAction()
	{
		$response = new Response();
		$response->headers->set('Accept', 'application/json');
		$response->headers->set('Access-Control-Allow-Origin', '*');
		$response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
		$response->headers->set('Access-Control-Allow-Headers', 'content-type, x-requested-with, x-requested-by, x-storageapi-url, x-storageapi-token, x-kbc-runid');
		$response->headers->set('Access-Control-Max-Age', '86400');
		$response->headers->set('Content-Type', 'application/json');
		$response->send();

		return $response;
	}

	/** Jobs */

	protected function getJobUrl($jobId)
	{
		$queueParams = $this->container->getParameter('queue');
		return $queueParams['url'] . '/job/' . $jobId;
	}

	/**
	 * @return JobManager
	 */
	protected function getJobManager()
	{
		return $this->container->get('syrup.job_manager');
	}

	protected function getMapping()
	{
		$mappingJson = $this->renderView('@elasticsearch/mapping.json.twig');

		return json_decode($mappingJson, true);
	}

	protected function checkMappingParams($params)
	{
		$mappingParams = $this->getMapping()['mappings']['jobs']['properties']['params']['properties'];

		foreach (array_keys($params) as $paramKey) {
			if (!in_array($paramKey, array_keys($mappingParams))) {
				throw new UserException(sprintf("Parameter '%s' is not allowed. Allowed params are '%s'", $paramKey, implode(',', array_keys($mappingParams))));
			}
		}
	}

	/**
	 * @param string $command
	 * @param array $params
	 * @return JobInterface
	 */
	protected function createJob($command, $params)
	{
		$request = $this->container->get('request');
		$tokenData = $this->storageApi->verifyToken();

		return new Job([
			'id'    => $this->storageApi->generateId(),
			'runId'     => $this->getRunId($request),
			'project'   => [
				'id'        => $tokenData['owner']['id'],
				'name'      => $tokenData['owner']['name']
			],
			'token'     => [
				'id'            => $tokenData['id'],
				'description'   => $tokenData['description'],
				'token'         => $this->getEncryptor()->encrypt($this->storageApi->getTokenString())
			],
			'component' => $this->componentName,
			'command'   => $command,
			'params'    => $params,
			'process'   => [
				'host'  => gethostname(),
				'pid'   => getmypid()
			],
			'createdTime'   => date('c')
		]);
	}

	/**
	 * Add JobId to queue
	 *
	 * @param        $jobId
	 * @param string $queueName
	 * @param array  $otherData
	 */
	protected function enqueue($jobId, $queueName = 'default', $otherData = [])
	{
		$data = [
			'jobId'     => $jobId,
			'component' => $this->componentName
		];

		if (count($otherData)) {
			$data = array_merge($data, $otherData);
		}

		/** @var QueueService $queue */
		$queue = $this->container->get('syrup.queue_factory')->get($queueName);
		$queue->enqueue($data);
	}

	/** Stuff */

	/**
	 * @return EncryptorInterface
	 */
	protected function getEncryptor()
	{
		return $this->container->get('syrup.encryptor');
	}

	protected function sendEventToSapi($type, $message, $componentName)
	{
		$sapiEvent = new SapiEvent();
		$sapiEvent->setComponent($componentName);
		$sapiEvent->setMessage($message);
		$sapiEvent->setRunId($this->container->get('syrup.monolog.json_formatter')->getRunId());
		$sapiEvent->setType($type);

		$this->storageApi->createEvent($sapiEvent);
	}

	/**
	 * @param String $actionName
	 * @param String $status
	 * @param Int $startTime
	 * @param Int $endTime
	 * @param String $params
	 */
	protected function logToSharedSapi($actionName, $status, $startTime, $endTime, $params)
	{
		$logData = $this->storageApi->getLogData();

		$ssEvent = new JobEvent([
			'component' => $this->component->getFullName(),
			'action'    => $actionName,
			'url'       => $this->getRequest()->getUri(),
			'projectId' => $logData['owner']['id'],
			'projectName'    => $logData['owner']['name'],
			'tokenId'   => $logData['id'],
			'tokenDesc' => $logData['description'],
			'status'    => $status,
			'startTime' => $startTime,
			'endTime'   => $endTime,
			'request'   => $params
		]);

		try {
			$this->getSharedSapi()->log($ssEvent);
		} catch (\Exception $e) {
			$this->logger->warning("Error while logging into Shared SAPI", [
				"message"   => $e->getMessage(),
				"exception" => $e->getTraceAsString()
			]);
		}
	}

	protected function getRunId(Request $request)
	{
		if ($request->headers->has('x-kbc-runid')) {
			return $request->headers->get('x-kbc-runid');
		}
		return $this->storageApi->generateId();
	}

	/**
	 * @return SharedSapiService
	 */
	protected function getSharedSapi()
	{
		return $this->container->get('syrup.shared_sapi');
	}

	public function camelize($value)
	{
		if(!is_string($value)) {
			return $value;
		}

		$chunks = explode('-', $value);
		$ucfirsted = array_map(function($s) {
			return ucfirst($s);
		}, $chunks);

		return lcfirst(implode('', $ucfirsted));
	}

}
