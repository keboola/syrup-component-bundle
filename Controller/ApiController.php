<?php

namespace Syrup\ComponentBundle\Controller;

use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Keboola\StorageApi\Client;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Keboola\StorageApi\Event as SapiEvent;

class ApiController extends ContainerAware
{
	/**
	 * @var Client
	 */
	protected $_storageApi;

	public function preExecute()
	{
		$request = $this->getRequest();

		if ($request->headers->has('X-StorageApi-Token')) {
			$url = null;
			if ($request->headers->has('X-StorageApi-Url')) {
				$url = $request->headers->get('X-StorageApi-Url');
			}
			$this->_storageApi = new Client($request->headers->get('X-StorageApi-Token'), $url);
			$this->container->set('storageApi', $this->_storageApi);

			$kbcRunId = $this->_storageApi->generateId();
			if ($request->headers->has('X-KBC-RunId')) {
				$kbcRunId = $request->headers->get('X-KBC-RunId');
			}

			$this->container->get('syrup.monolog.json_formatter')->setRunId($kbcRunId);
			$this->container->get('syrup.monolog.json_formatter')->setStorageApiClient($this->_storageApi);

		} else {
			throw new HttpException('Missing StorageAPI token.');
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

	    $this->container->get('syrup.monolog.json_formatter')->setComponentName($componentName);
	    $component = $this->container->get('syrup.component_factory')->get($this->_storageApi, $componentName);
	    $component->setContainer($this->container);

	    if (!method_exists($component, $actionName)) {
		    throw new HttpException(400, "Component $componentName doesn't have action $actionName");
	    }

	    $component->$actionName(json_decode($request->getContent(), true));

	    $duration = microtime(true) - $timestart;

	    $response = new Response(json_encode(array(
		    'status'    => 'ok',
		    'duration'  => $duration
	    )));

	    // Create Success event in SAPI
	    $this->_sendSuccessEventToSapi('Action "'.$actionName.'" finished. Duration: ' . $duration, $componentName);

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

}
