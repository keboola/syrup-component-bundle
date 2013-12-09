<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Miro
 * Date: 23.11.12
 * Time: 15:09
 */
namespace Syrup\ComponentBundle\Listener;

use Monolog\Logger;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class SyrupControllerListener
{
	/**
	 * @var Logger
	 */
	protected $logger;

	public function __construct(Logger $logger)
	{
		$this->logger = $logger;
	}

	public function onKernelController(FilterControllerEvent $event)
	{
		if (HttpKernelInterface::MASTER_REQUEST === $event->getRequestType()) {
			$controllers = $event->getController();
			if (is_array($controllers)) {
				$controller = $controllers[0];

				if (
					is_object($controller)
					&& method_exists($controller, 'preExecute')
					&& $event->getRequest()->getMethod() != 'OPTIONS'
				) {
					$controller->preExecute();
				}
			}
		}
	}

	public function onKernelResponse(FilterResponseEvent $event)
	{
		$request = $event->getRequest();

		$pathInfo = explode('/', $request->getPathInfo());
		$componentName = $pathInfo[1];
		$actionName = $pathInfo[2];

		$this->logger->info('Component ' . $componentName . ' finished action ' . $actionName);
	}
}
