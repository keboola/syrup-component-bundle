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
	protected $_logger;

	public function __construct(Logger $logger)
	{
		$this->_logger = $logger;
	}

	public function onKernelException(GetResponseForExceptionEvent $event)
	{
		// You get the exception object from the received event
		$exception = $event->getException();

		// Customize your response object to display the exception details
		$response = new Response();
		$response->setContent(json_encode(array(
			'status'    => 'error',
			'message'   => $exception->getMessage(),
			'code'      => $exception->getCode()
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
		$this->_logger->err($exception->getMessage());
	}
}
