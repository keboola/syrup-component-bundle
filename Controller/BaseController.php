<?php
/**
 * Created by Miroslav Čillík <miro@keboola.com>
 * Date: 20/01/14
 * Time: 13:34
 */

namespace Syrup\ComponentBundle\Controller;

use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Syrup\ComponentBundle\Exception\UserException;
use Syrup\ComponentBundle\Filesystem\Temp;

class BaseController extends Controller
{
	/** @var Logger */
	protected $logger;

	/** @var Temp */
	protected $temp;

	/** @var String */
	protected $componentName;


	public function preExecute(Request $request)
	{
		$pathInfo = explode('/', $request->getPathInfo());
		$this->componentName = $pathInfo[1];
		$actionName = $pathInfo[2];

		$this->initLogger();
		$this->initTemp();

		$this->logger->debug('Component ' . $this->componentName . ' started action ' . $actionName);
	}

	protected function initTemp()
	{
		$this->temp = $this->get('syrup.temp');
	}

	protected function initLogger()
	{
		$this->logger = $this->container->get('logger');
	}

	public function createResponse($content = '', $status = '200', $headers = array())
	{
		return new Response($content, $status, $this->commonHeaders($headers));
	}

	public function createJsonResponse(array $data, $status = '200', $headers = array())
	{
		return new JsonResponse($data, $status, $this->commonHeaders($headers));
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

	protected function commonHeaders($headers)
	{
		$headers['Access-Control-Allow-Origin'] = '*';
		$headers['Access-Control-Allow-Methods'] = '*';
		$headers['Access-Control-Allow-Headers'] = '*';

		$headers['Cache-Control'] = 'private, no-cache, no-store, must-revalidate';

		return $headers;
	}

}
