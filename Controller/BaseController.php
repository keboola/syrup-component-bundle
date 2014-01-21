<?php
/**
 * Created by Miroslav Čillík <miro@keboola.com>
 * Date: 20/01/14
 * Time: 13:34
 */

namespace Syrup\ComponentBundle\Controller;


use Keboola\Encryption\AesEncryptor;
use Keboola\Encryption\EncryptorInterface;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Syrup\ComponentBundle\Component\Component;
use Syrup\ComponentBundle\Exception\UserException;
use Syrup\ComponentBundle\Filesystem\TempService;

class BaseController extends Controller
{
	/** @var Component */
	protected $component;

	/** @var Logger */
	protected $logger;

	/** @var TempService */
	protected $temp;

	/** @var String */
	protected $componentName;


	public function preExecute()
	{
		/** @var Request $request */
		$request = $this->getRequest();

		$pathInfo = explode('/', $request->getPathInfo());
		$this->componentName = $pathInfo[1];
		$actionName = $pathInfo[2];

		$this->initLogger($this->componentName);
		$this->initTempService($this->componentName);
		$this->initEncryptor($this->componentName);

		$this->logger->info('Component ' . $this->componentName . ' started action ' . $actionName);
	}

	protected function initTempService($componentName)
	{
		$this->temp = $this->get('syrup.temp_factory')->get($componentName);
		$this->container->set('syrup.temp_service', $this->temp);
	}

	protected function initLogger($componentName)
	{
		$this->get('syrup.monolog.json_formatter')->setComponentName($componentName);
		$this->logger = $this->container->get('logger');
	}

	// @TODO refactor these using Request in container
	protected function initEncryptor($componentName)
	{
		$this->container->set('syrup.encryptor', $this->get('syrup.encryptor_factory')->get($componentName));
	}

	public function createResponse($content = '', $status = '200', $headers = array())
	{
		$headers[] = array('Access-Control-Allow-Origin' => '*');
		return new Response($content, $status, $headers);

	}

	public function createJsonResponse(array $data, $status = '200', $headers = array())
	{
		$headers[] = array('Access-Control-Allow-Origin' => '*');
		return new JsonResponse($data, $status, $headers);
	}

	/**
	 * Extracts POST data in JSON from request
	 * @param \Symfony\Component\HttpFoundation\Request $request
	 * @throws HttpException
	 * @return array
	 */
	protected function getPostJson(Request $request)
	{
		$return = array();
		$body = $request->getContent();

		if (!empty($body) && !is_null($body) && $body != 'null') {
			$return = json_decode($body, true);

			if (null === $return || !is_array($return)) {
				throw new UserException("Bad JSON format of request body");
			}
		}

		return $return;
	}

} 