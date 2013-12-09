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
use Syrup\ComponentBundle\Exception\SyrupExceptionInterface;
use Syrup\ComponentBundle\Monolog\Formatter\SyrupJsonFormatter;

class SyrupExceptionListener
{
	/**
	 * @var \Monolog\Logger
	 */
	protected $_logger;

	protected $_formatter;

	public function __construct(Logger $logger, SyrupJsonFormatter $formatter)
	{
		$this->_logger = $logger;
		$this->_formatter = $formatter;
	}

	public function onKernelException(GetResponseForExceptionEvent $event)
	{
		// You get the exception object from the received event
		$exception = $event->getException();
		$exceptionId = $this->_formatter->getComponentName() . '-' . md5(microtime());

		// Customize your response object to display the exception details
		$response = new Response();
		$content = array(
			'status'    => 'error',
			'error'     => 'Application error',
			'code'      => $exception->getCode(),
			'message'   => $exception->getMessage(),
			'exceptionId'   => $exceptionId,
			'runId'     => $this->_formatter->getRunId()
		);

		// SyrupExceptionInterface holds additional data
		if ($exception instanceof SyrupExceptionInterface) {
			$content['data'] = $exception->getData();
		}

		// HttpExceptionInterface is a special type of exception that
		// holds status code and header details
		if ($exception instanceof HttpExceptionInterface) {
			$response->setStatusCode($exception->getStatusCode());
			$response->headers->replace($exception->getHeaders());

			if ($exception->getStatusCode() < 500) {
				$content['error'] = 'User error';
			}

		} else {
			$response->setStatusCode(500);
			$content['message'] = 'Contact support@keboola.com and attach this exception id.';
		}

		$response->setContent(json_encode($content));

		$response->headers->set('Content-Type', 'application/json');
		$response->headers->set('Access-Control-Allow-Origin', '*');

		// Send the modified response object to the event
		$event->setResponse($response);

		// Log exception
		$method = 'error';
		if ($response->getStatusCode() >= 500) {
			$method = 'critical';
		}
		$this->_logger->$method(
			$exception->getMessage(),
			array(
				'exception'     => $exception,
				'exceptionId'   => $exceptionId,
			)
		);
	}
}
