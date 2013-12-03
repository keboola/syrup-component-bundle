<?php

namespace Syrup\ComponentBundle\Controller;

use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\Form\Exception\Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Keboola\StorageApi\Client;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Keboola\StorageApi\Event as SapiEvent;
use Syrup\ComponentBundle\Component\Component;
use Syrup\ComponentBundle\Component\ComponentFactory;
use Syrup\ComponentBundle\Filesystem\Temp;
use Syrup\ComponentBundle\Service\SharedSapi\jobEvent;
use Syrup\ComponentBundle\Service\SharedSapi\SharedSapi;

class ApiController extends ContainerAware
{
	/** @var Client */
	protected $storageApi;

	/** @var Component */
	protected $component;

	protected function initStorageApi(Request $request)
	{
		$url = null;

		try {
			$url = $this->container->getParameter('storageApi.url');
		} catch (\Exception $e) {
			// storageApi.url not defined in config - do nothing
		}

		if ($request->headers->has('X-StorageApi-Url')) {
			$url = $request->headers->get('X-StorageApi-Url');
		}

		$this->storageApi = new Client($request->headers->get('X-StorageApi-Token'), $url);
        $this->container->set('storageApi', $this->storageApi);

		if ($request->headers->has('X-KBC-RunId')) {
			$kbcRunId = $request->headers->get('X-KBC-RunId');
		} else {
			$kbcRunId = $this->storageApi->generateId();
		}

		$this->storageApi->setRunId($kbcRunId);
		$this->container->get('syrup.monolog.json_formatter')->setRunId($kbcRunId);
		$this->container->get('syrup.monolog.json_formatter')->setStorageApiClient($this->storageApi);
	}

	protected function initFilesystem()
	{
		$temp = new Temp($this->component);
		$this->container->set('filesystem_temp', $temp);
	}

	public function preExecute()
	{
		$request = $this->getRequest();

		if ($request->headers->has('X-StorageApi-Token')) {
			$this->initStorageApi($request);
		} else {
			throw new HttpException(400, 'Missing StorageAPI token.');
		}

		$pathInfo = explode('/', $request->getPathInfo());
		$componentName = $pathInfo[1];
		$actionName = $pathInfo[2];

		$this->container->get('syrup.monolog.json_formatter')->setComponentName($componentName);

		// $this->initSharedConfig($componentName);

		/** @var ComponentFactory $componentFactory */
		$componentFactory = $this->container->get('syrup.component_factory');
		$this->component = $componentFactory->get($this->storageApi, $componentName);


		//@TODO remove in future
		$this->initFilesystem();

		$this->component->setContainer($this->container);

		/** @var Logger $logger */
		$logger = $this->container->get('logger');
		$logger->info('Component ' . $componentName . ' started action ' . $actionName);
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
		        $body = $request->getContent();
				if (!empty($body) && !is_null($body) && $body != 'null') {
					$arr = json_decode($body, true);

					if (null === $arr || !is_array($arr)) {
						throw new HttpException(400, "Bad JSON format of request body - " . var_export($body, true));
					}
					$params = $arr;
				}
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

		    $response = new Response(json_encode($responseBody));
		    $response->headers->set('Access-Control-Allow-Origin', '*');
	    }

	    // Create Success event in SAPI
	    $this->sendEventToSapi(SapiEvent::TYPE_SUCCESS, 'Action "'.$actionName.'" finished. Duration: ' . $duration, $componentName);

	    // Log to Shared SAPI
	    $this->logToSharedSapi($actionName, $status, $timestart, $timeend, json_encode($params));

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

	/**
	 * @return Request
	 */
	public function getRequest()
	{
		return $this->container->get('request');
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
			'token'     => $this->storageApi->getTokenString(),
			'tokenId'   => $logData['id'],
			'tokenDesc' => $logData['description'],
			'tokenOwnerName'    => $logData['owner']['name'],
			'status'    => $status,
			'startTime' => $startTime,
			'endTime'   => $endTime,
			'request'   => $params
	    ));
		$this->getSharedSapi()->log($ssEvent);
	}

	/**
	 * @return SharedSapi
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
