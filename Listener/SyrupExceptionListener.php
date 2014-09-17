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
use Syrup\ComponentBundle\Monolog\Formatter\SyrupJsonFormatter;
use Syrup\CoreBundle\Debug\ExceptionHandler;

class SyrupExceptionListener extends ErrorHandler
{
	/**
	 * @var \Monolog\Logger
	 */
	protected $logger;

	protected $formatter;

	private $prevErrorHandler;

	private $levels = array(
		E_WARNING           => 'Warning',
		E_NOTICE            => 'Notice',
		E_USER_ERROR        => 'User Error',
		E_USER_WARNING      => 'User Warning',
		E_USER_NOTICE       => 'User Notice',
		E_STRICT            => 'Runtime Notice',
		E_RECOVERABLE_ERROR => 'Catchable Fatal Error',
		E_DEPRECATED        => 'Deprecated',
		E_USER_DEPRECATED   => 'User Deprecated',
		E_ERROR             => 'Error',
		E_CORE_ERROR        => 'Core Error',
		E_COMPILE_ERROR     => 'Compile Error',
		E_PARSE             => 'Parse',
	);

	public function __construct(Logger $logger, SyrupJsonFormatter $formatter)
	{
		$this->logger = $logger;
		$this->formatter = $formatter;

		$this->prevErrorHandler = set_error_handler(array($this, 'handle'));
		register_shutdown_function(array($this, 'handleFatal'));
	}

	public function handle($level, $message, $file = 'unknown', $line = 0, $context = array())
	{
		$exceptionId = $this->formatter->getAppName() . '-' . md5(microtime());

		$code = ($level < 300 || $level >= 600) ? 500 : $level;

		// Log
		$method = 'error';
		if ($code >= 500) {
			$method = 'critical';
		}
		$this->logger->$method(
			$message,
			array(
				'exception'     => new ApplicationException($message),
				'exceptionId'   => $exceptionId,
			)
		);

		if (is_array($this->prevErrorHandler) && $this->prevErrorHandler[0] instanceof ErrorHandler) {
			$this->prevErrorHandler[0]->handle($level, $message, $file, $line, $context);
		}
	}

	public function handleFatal()
	{
		if (null === $error = error_get_last()) {
			return;
		}

		$type = $error['type'];
		if (!in_array($type, array(E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE))) {
			return;
		}

		$level = isset($this->levels[$type]) ? $this->levels[$type] : $type;
		$message = sprintf('%s: %s in %s line %d', $level, $error['message'], $error['file'], $error['line']);
		$exception = new FatalErrorException($message, 0, $type, $error['file'], $error['line']);

		$this->logger->critical($error['message'], array(
			'exception' => $exception
		));

		// get current exception handler
		$exceptionHandler = set_exception_handler(function () {});
		restore_exception_handler();

		if (is_array($exceptionHandler) && $exceptionHandler[0] instanceof ExceptionHandler) {
			$exceptionHandler[0]->handle($exception);
		}
	}

	public function onConsoleException(ConsoleExceptionEvent $event)
	{
		$exception = $event->getException();
		$exceptionId = $this->formatter->getAppName() . '-' . md5(microtime());

		$code = ($exception->getCode() < 300 || $exception->getCode() >= 600) ? 500 : $exception->getCode();
		$content = array(
			'status'    => 'error',
			'error'     => ($code < 500) ? 'User error' : 'Application error',
			'code'      => $code,
			'message'   => ($code < 500) ? $exception->getMessage() : 'Contact support@keboola.com and attach this exception id.',
			'exceptionId'   => $exceptionId,
			'runId'     => $this->formatter->getRunId()
		);

		// SyrupExceptionInterface holds additional data
		if ($exception instanceof SyrupExceptionInterface) {
			$content['data'] = $exception->getData();
		}

		// Log exception
		$method = 'error';
		if ($code >= 500) {
			$method = 'critical';
		}
		$this->logger->$method(
			$exception->getMessage(),
			array(
				'exception'     => $exception,
				'exceptionId'   => $exceptionId,
			)
		);
	}

	public function onKernelRequest(GetResponseEvent $event)
	{
	}

	public function onKernelException(GetResponseForExceptionEvent $event)
	{
		// You get the exception object from the received event
		$exception = $event->getException();

		// On dummy exception do nothing
		if ($exception instanceof DummyException) {
			$event->stopPropagation();
			return;
		}

		$exceptionId = $this->formatter->getAppName() . '-' . md5(microtime());

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
			'runId'     => $this->formatter->getRunId()
		);

		// SyrupExceptionInterface holds additional data
		if ($exception instanceof SyrupExceptionInterface) {
			$content['data'] = $exception->getData();
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
		$method = 'error';
		if ($code >= 500) {
			$method = 'critical';
		}
		$this->logger->$method(
			$exception->getMessage(),
			array(
				'exception'     => $exception,
				'exceptionId'   => $exceptionId,
			)
		);
	}
}
