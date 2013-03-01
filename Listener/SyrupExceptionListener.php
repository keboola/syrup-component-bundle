<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Miro
 * Date: 23.11.12
 * Time: 14:55
 */
namespace Syrup\ComponentBundle\Listener;

use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Monolog\Logger;

class SyrupExceptionListener
{
	/**
	 * @var \Monolog\Logger
	 */
	protected $_logger;

	protected $_appName;

	public function __construct(Logger $logger, $appName)
	{
		$this->_logger = $logger;
		$this->_appName = $appName;
	}

	public function onKernelException(GetResponseForExceptionEvent $event)
	{
		// You get the exception object from the received event
		$exception = $event->getException();

		$exceptionId = $this->_appName . '-' . md5(microtime());

		// Customize your response object to display the exception details
		$response = new Response();
		$response->setContent(json_encode(array(
			'status'    => 'error',
			'error'     => 'Application error',
			'code'      => $exception->getCode(),
			'message'   => $exception->getMessage(),
			'exceptionId'   => $exceptionId
		)));

		// HttpExceptionInterface is a special type of exception that
		// holds status code and header details
		if ($exception instanceof HttpExceptionInterface) {
			$response->setStatusCode($exception->getStatusCode());
			$response->headers->replace($exception->getHeaders());
		} else {
			$response->setStatusCode(500);
		}

		$response->headers->set('Content-Type', 'application/json');

		// Send the modified response object to the event
		$event->setResponse($response);

		// Log exception
		$this->_logger->err(
			$exception->getMessage(),
			array(
				'exception'     => $exception,
				'exceptionId'   => $exceptionId,
			)
		);
	}
}
