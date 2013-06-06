<?php
/**
 * Application.php
 *
 * @author: Miroslav Čillík <miro@keboola.com>
 * @created: 17.4.13
 */

namespace Syrup\ComponentBundle\Console;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application as BaseApplication;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class Application extends BaseApplication {

	private $originalAutoExit;

	public function __construct(KernelInterface $kernel)
	{
		parent::__construct($kernel);
		$this->originalAutoExit = true;
	}

	/**
	 * Runs the current application.
	 *
	 * @param InputInterface  $input  An Input instance
	 * @param OutputInterface $output An Output instance
	 *
	 * @return integer 0 if everything went fine, or an error code
	 *
	 * @throws \Exception When doRun returns Exception
	 *
	 * @api
	 */
	public function run(InputInterface $input = null, OutputInterface $output = null)
	{
		// make the parent method throw exceptions, so you can log it
		$this->setCatchExceptions(false);

		if (null === $input) {
			$input = new ArgvInput();
		}

		if (null === $output) {
			$output = new ConsoleOutput();
		}

		try {
			$statusCode = parent::run($input, $output);
		} catch (\Exception $e) {

			$message = sprintf(
				'%s: %s (uncaught exception) at %s line %s while running console command `%s`',
				get_class($e),
				$e->getMessage(),
				$e->getFile(),
				$e->getLine(),
				$this->getCommandName($input)
			);

			echo $message;

			/** @var $logger LoggerInterface */
			$logger = $this->getKernel()->getContainer()->get('logger');

			$logger->critical(
				$e->getMessage(),
				array(
					'command'       => $this->getCommandName($input),
					'exception'     => $e,
					'exceptionId'   => $this->getCommandName($input) . '-' . md5(microtime()),
				)
			);

			if ($output instanceof ConsoleOutputInterface) {
				$this->renderException($e, $output->getErrorOutput());
			} else {
				$this->renderException($e, $output);
			}
			$statusCode = $e->getCode();

			$statusCode = is_numeric($statusCode) && $statusCode ? $statusCode : 1;
		}

		if ($this->originalAutoExit) {
			if ($statusCode > 255) {
				$statusCode = 255;
			}
			// @codeCoverageIgnoreStart
			exit($statusCode);
			// @codeCoverageIgnoreEnd
		}

		return $statusCode;
	}

	public function setAutoExit($bool)
	{
		// parent property is private, so we need to intercept it in a setter
		$this->originalAutoExit = (Boolean) $bool;
		parent::setAutoExit($bool);
	}

}
