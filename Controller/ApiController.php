<?php

namespace Syrup\ComponentBundle\Controller;

use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\Form\Exception\Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Keboola\StorageApi\Client;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Keboola\StorageApi\Event as SapiEvent;
use Syrup\ComponentBundle\Component\Component;
use Syrup\ComponentBundle\Filesystem\Temp;

class ApiController extends ContainerAware
{
	/**
	 * @var Client
	 */
	protected $_storageApi;

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

		$this->_storageApi = new Client($request->headers->get('X-StorageApi-Token'), $url);
        $this->container->set('storageApi', $this->_storageApi);

		if ($request->headers->has('X-KBC-RunId')) {
			$kbcRunId = $request->headers->get('X-KBC-RunId');
		} else {
			$kbcRunId = $this->_storageApi->generateId();
		}

		$this->_storageApi->setRunId($kbcRunId);
		$this->container->get('syrup.monolog.json_formatter')->setRunId($kbcRunId);
		$this->container->get('syrup.monolog.json_formatter')->setStorageApiClient($this->_storageApi);
	}

	protected function initSharedConfig($componentName)
	{
		$components = $this->container->getParameter('components');
		if (isset($components[$componentName]['shared_sapi']['token'])) {
			$token = $components[$componentName]['shared_sapi']['token'];
			$url = null;
			if (isset($components[$componentName]['shared_sapi']['url'])) {
				$url = $components[$componentName]['shared_sapi']['url'];
			}
			$sharedSapi = new Client($token, $url);
			$this->container->set('shared_sapi', $sharedSapi);
		}
	}

	protected function initFilesystem(Component $component)
	{
		$temp = new Temp($component);
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

        //@TODO refactor shared config to Config object
	    $this->initSharedConfig($componentName);

	    $this->container->get('syrup.monolog.json_formatter')->setComponentName($componentName);
	    /** @var Component $component */
	    $component = $this->container->get('syrup.component_factory')->get($this->_storageApi, $componentName);

	    $this->initFilesystem($component);

	    $component->setContainer($this->container);

	    $funcName = strtolower($method) . ucfirst($this->camelize($actionName));

	    if (!method_exists($component, $funcName)) {
		    $funcName2 = $this->camelize($actionName);
		    if (!method_exists($component, $funcName2)) {
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

	    $componentResponse = $component->$funcName($params);

	    if ($componentResponse instanceof Response) {
		    return $componentResponse;
	    }

	    $duration = microtime(true) - $timestart;

	    $responseBody = array(
		    'status'    => 'ok',
		    'duration'  => $duration
	    );

	    if (null != $componentResponse) {
		    $responseBody = array_merge($componentResponse, $responseBody);
	    }

	    $response = new Response(json_encode($responseBody));
		$response->headers->set('Access-Control-Allow-Origin', '*');

	    // Create Success event in SAPI
	    $this->_sendSuccessEventToSapi('Action "'.$actionName.'" finished. Duration: ' . $duration, $componentName);

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

	protected function _sendSuccessEventToSapi($message, $componentName)
	{
		$sapiEvent = new SapiEvent();
		$sapiEvent->setComponent($componentName);
		$sapiEvent->setMessage($message);
		$sapiEvent->setRunId($this->container->get('syrup.monolog.json_formatter')->getRunId());
		$sapiEvent->setType(SapiEvent::TYPE_SUCCESS);

		$this->_storageApi->createEvent($sapiEvent);
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
