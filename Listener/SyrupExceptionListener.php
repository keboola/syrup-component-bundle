<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Miro
 * Date: 23.11.12
 * Time: 14:55
 */
namespace Syrup\ComponentBundle\Listener;

use Symfony\Component\Console\Event\ConsoleExceptionEvent;
use Symfony\Component\Debug\Exception\DummyException;
use Symfony\Component\Debug\ErrorHandler;
use Symfony\Component\Debug\Exception\FatalErrorException;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Monolog\Logger;
use Syrup\ComponentBundle\Exception\ApplicationException;
use Syrup\ComponentBundle\Exception\SyrupExceptionInterface;
use Syrup\ComponentBundle\Exception\UserException;
use Syrup\ComponentBundle\Monolog\Formatter\SyrupJsonFormatter;
use Syrup\CoreBundle\Debug\ExceptionHandler;

class SyrupExceptionListener
{
	/**
	 * @var \Monolog\Logger
	 */
	protected $logger;

	protected $formatter;

	public function __construct(Logger $logger, SyrupJsonFormatter $formatter)
	{
		$this->logger = $logger;
		$this->formatter = $formatter;
	}

	public function onConsoleException(ConsoleExceptionEvent $event)
	{
		$exception = $event->getException();
		$exceptionId = $this->formatter->getAppName() . '-' . md5(microtime());

		$code = ($exception->getCode() < 300 || $exception->getCode() >= 600) ? 500 : $exception->getCode();

		if ($exception instanceof HttpExceptionInterface) {
			$code = $exception->getStatusCode();
		}

		$content = array(
			'status'    => 'error',
			'error'     => 'Application error',
			'code'      => $code,
			'message'   => 'Contact support@keboola.com and attach this exception id.',
			'exceptionId'   => $exceptionId,
			'runId'     => $this->formatter->getRunId()
		);

		$method = 'critical';
		if ($exception instanceof UserException) {
			$method = 'error';
			$content['error'] = 'User error';
			$content['message'] = $exception->getMessage();
		}

		$logData = array(
			'exception'     => $exception,
			'exceptionId'   => $exceptionId,
		);

		// SyrupExceptionInterface holds additional data
		if ($exception instanceof SyrupExceptionInterface) {
			$logData['data'] = $exception->getData();
			$content['data'] = $exception->getData();
		}

		// Log exception
		$this->logger->$method($exception->getMessage(), $logData);
	}

	public function onKernelException(GetResponseForExceptionEvent $event)
	{
		$requestData = [
			'url'   => $event->getRequest()->getUri(),
			'query' => $event->getRequest()->query->all(),
			'body'  => $event->getRequest()->getContent()
		];

		// You get the exception object from the received event
		$exception = $event->getException();

		$exceptionId = $this->formatter->getAppName() . '-' . md5(microtime());

		// Customize your response object to display the exception details
		$response = new Response();

		$code = ($exception->getCode() < 300 || $exception->getCode() >= 600) ? 500 : $exception->getCode();

		// exception is by default Application Exception
		$isUserException = false;

		// HttpExceptionInterface is a special type of exception that
		// holds status code and header details
		if ($exception instanceof HttpExceptionInterface) {
			$code = $exception->getStatusCode();
			$response->headers->replace($exception->getHeaders());

			if ($code < 500) {
				$isUserException = true;
			}
		}

		$content = array(
			'status'    => 'error',
			'error'     => 'Application error',
			'code'      => $code,
			'message'   => 'Contact support@keboola.com and attach this exception id.',
			'request'   => $requestData,
			'exceptionId'   => $exceptionId,
			'runId'     => $this->formatter->getRunId()
		);

		$method = 'critical';
		if ($isUserException) {
			$method = 'error';
			$content['error'] = 'User error';
			$content['message'] = $exception->getMessage();
		}

		$logData = array(
			'request'       => $requestData,
			'exception'     => $exception,
			'exceptionId'   => $exceptionId,
		);

		// SyrupExceptionInterface holds additional data
		if ($exception instanceof SyrupExceptionInterface) {
			$content['data'] = $exception->getData();
			$logData['data'] = $exception->getData();
		}

		$response->setContent(json_encode($content));
		$response->setStatusCode($code);
		$response->headers->set('Content-Type', 'application/json');
		$response->headers->set('Access-Control-Allow-Origin', '*');
		$response->headers->set('Access-Control-Allow-Methods', '*');
		$response->headers->set('Access-Control-Allow-Headers', '*');
		$response->headers->set('Cache-Control', 'private, no-cache, no-store, must-revalidate');

		// Send the modified response object to the event
		$event->setResponse($response);

		// Log exception
		$this->logger->$method($exception->getMessage(), $logData);
	}
}
