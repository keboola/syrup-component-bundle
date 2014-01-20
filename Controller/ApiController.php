<?php

namespace Syrup\ComponentBundle\Controller;

use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Keboola\StorageApi\Client;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Keboola\StorageApi\Event as SapiEvent;
use Syrup\ComponentBundle\Component\Component;
use Syrup\ComponentBundle\Component\ComponentFactory;
use Syrup\ComponentBundle\Filesystem\Temp;
use Syrup\ComponentBundle\Filesystem\TempService;
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

	/**
	 * @deprecated - will be removed in 1.4.0, use TempService instead
	 */
	protected function initFilesystem(Component $component)
	{
		$temp = new Temp($component);
		$this->container->set('filesystem_temp', $temp);
	}

	/**
	 * @TODO refactor using Request object in container in Symfony 2.4
	 */
	protected function initComponent(Client $storageApi, $componentName)
	{
		/** @var ComponentFactory $componentFactory */
		$componentFactory = $this->container->get('syrup.component_factory');
		$this->component = $componentFactory->get($storageApi, $componentName);
		$this->component->setContainer($this->container);
	}

	public function preExecute()
	{
		parent::preExecute();

		$this->initStorageApi();
		$this->initComponent($this->storageApi, $this->componentName);

		//@TODO remove in 1.4.0
		$this->initFilesystem($this->component);
	}

	/**
	 * @param string $componentName
	 * @param string $actionName
	 * @throws \Symfony\Component\HttpKernel\Exception\HttpException
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function runAction($componentName, $actionName)
    {
	    set_time_limit(3600*3);
	    $timestart = microtime(true);

	    $request = $this->getRequest();
	    $params = array();
	    $method = $request->getMethod();

	    $funcName = strtolower($method) . ucfirst($this->camelize($actionName));

	    if (!method_exists($this->component, $funcName)) {
		    $funcName2 = $this->camelize($actionName);
		    if (!method_exists($this->component, $funcName2)) {
			    throw new HttpException(400, "Component $componentName doesn't have function $funcName or $funcName2");
		    }
		    $funcName = $funcName2;
	    }

	    switch ($method) {
		    case 'GET':
		    case 'DELETE':
			    $params = $request->query->all();
			    break;
			case 'POST':
		    case 'PUT':
		        $params = $this->getPostJson($request);
			    break;
	    }

	    $componentResponse = $this->component->$funcName($params);

	    $status = 'ok';
	    $timeend = microtime(true);
	    $duration = $timeend - $timestart;

	    if ($componentResponse instanceof Response) {
		    $response = $componentResponse;
		    $status = ($response->getStatusCode() == 200) ? 'ok' : 'error';
	    } else {
		    $responseBody = array(
			    'status'    => isset($componentResponse['status']) ? $componentResponse['status'] : $status,
			    'duration'  => $duration
		    );

		    if (null != $componentResponse) {
			    $responseBody = array_merge($componentResponse, $responseBody);
		    }

		    $response = $this->createJsonResponse($responseBody);
	    }

	    if ($actionName == 'run') {
		    // Create Success event in SAPI
		    $this->sendEventToSapi(SapiEvent::TYPE_SUCCESS, 'Action "'.$actionName.'" finished. Duration: ' . $duration, $componentName);

		    // Log to Shared SAPI
		    $this->logToSharedSapi($actionName, $status, $timestart, $timeend, json_encode($params));
	    }

	    return $response;
    }

	public function optionsAction($params)
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

		$ssEvent = new JobEvent(array(
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
	    ));

		try {
			$this->getSharedSapi()->log($ssEvent);
		} catch (\Exception $e) {
			$this->logger->warning("Error while logging into Shared SAPI", array(
				"message"   => $e->getMessage(),
				"exception" => $e->getTraceAsString()
			));
		}
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
