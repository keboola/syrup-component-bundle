<?php

namespace Syrup\ComponentBundle\Controller;

use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Keboola\StorageApi\Client;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

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
			$this->container->get('syrup.monolog.json_formatter')->setLogData($this->_storageApi->getLogData());
			$kbcRunId = $this->_storageApi->generateId();
			if ($request->headers->has('X-KBC-RunId')) {
				$kbcRunId = $request->headers->get('X-KBC-RunId');
			}
			$this->container->get('syrup.monolog.json_formatter')->setRunId($kbcRunId);

			$this->container->get('syrup.componentbundle.listener.exception')->setStorageApiClient($this->_storageApi);

		} else {
			throw new HttpException('Missing StorageAPI token.');
		}
	}

	/**
	 * @param string $componentName
	 * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function runAction($componentName)
    {
	    set_time_limit(3600*3);
	    $timestart = microtime(true);

	    $request = $this->getRequest();
	    if ($request->getMethod() != 'POST') {
		    throw new MethodNotAllowedHttpException("Only POST method is allowed.");
	    }

	    $this->container->get('syrup.monolog.json_formatter')->setComponentName($componentName);
	    $component = $this->container->get('syrup.component_factory')->get($this->_storageApi, $componentName);
	    $component->run(json_decode($request->getContent(), true));

	    $duration = microtime(true) - $timestart;

	    $response = new Response(json_encode(array(
		    'status'    => 'ok',
		    'duration'  => $duration
	    )));

	    return $response;
    }

	/**
	 * @return Request
	 */
	public function getRequest()
	{
		return $this->container->get('request');
	}

}
