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
		$code = ($exception->getCode() < 300 || $exception->getCode() >= 600) ? 500 : $exception->getCode();

		// HttpExceptionInterface is a special type of exception that
		// holds status code and header details
		if ($exception instanceof HttpExceptionInterface) {
			$code = $exception->getStatusCode();
			$response->headers->replace($exception->getHeaders());
		}

		$content = array(
			'status'    => 'error',
			'error'     => ($code < 500) ? 'User error' : 'Application error',
			'code'      => $code,
			'message'   => ($code < 500) ? $exception->getMessage() : 'Contact support@keboola.com and attach this exception id.',
			'exceptionId'   => $exceptionId,
			'runId'     => $this->_formatter->getRunId()
		);

		// SyrupExceptionInterface holds additional data
		if ($exception instanceof SyrupExceptionInterface) {
			$content['data'] = $exception->getData();
		}

		$response->setContent(json_encode($content));
		$response->setStatusCode($code);
		$response->headers->set('Content-Type', 'application/json');
		$response->headers->set('Access-Control-Allow-Origin', '*');

		// Send the modified response object to the event
		$event->setResponse($response);

		// Log exception
		$method = 'error';
		if ($code >= 500) {
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
