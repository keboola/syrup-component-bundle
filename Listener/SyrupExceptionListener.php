<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Miro
 * Date: 23.11.12
 * Time: 14:55
 */
namespace Syrup\ComponentBundle\Listener;

use Keboola\StorageApi\Client;
use Keboola\StorageApi\Event;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Monolog\Logger;
use Syrup\ComponentBundle\Monolog\Formatter\SyrupJsonFormatter;

class SyrupExceptionListener
{
	/**
	 * @var \Monolog\Logger
	 */
	protected $_logger;

	protected $_appName;

	protected $_componentName;

	protected $_formatter;

	/**
	 * @var Client
	 */
	protected $_sapiClient;

	public function __construct(Logger $logger, SyrupJsonFormatter $formatter)
	{
		$this->_logger = $logger;
		$this->_formatter = $formatter;
	}

	public function setStorageApiClient($client)
	{
		$this->_sapiClient = $client;
	}

	public function onKernelException(GetResponseForExceptionEvent $event)
	{
		// You get the exception object from the received event
		$exception = $event->getException();
		$exceptionId = $this->_formatter->getComponentName() . '-' . md5(microtime());

		// Customize your response object to display the exception details
		$response = new Response();
		$response->setContent(json_encode(array(
			'status'    => 'error',
			'error'     => 'Application error',
			'code'      => $exception->getCode(),
			'message'   => $exception->getMessage(),
			'exceptionId'   => $exceptionId,
			'runId'     => $this->_formatter->getRunId()
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
		$method = 'warn';
		if ($response->getStatusCode() >= 500) {
			$method = 'err';
		}
		$this->_logger->$method(
			$exception->getMessage(),
			array(
				'exception'     => $exception,
				'exceptionId'   => $exceptionId,
			)
		);

		// Log to SAPI events
		$sapiEvent = new Event();
		$sapiEvent->setComponent($this->_componentName);
		$sapiEvent->setMessage("Error occured.");
		$sapiEvent->setDescription($exception->getMessage());
		$sapiEvent->setRunId($this->_formatter->getRunId());
		$type = ($method=='err')?Event::TYPE_ERROR:Event::TYPE_WARN;
		$sapiEvent->setType($type);

		$this->_sapiClient->createEvent($sapiEvent);
	}
}
