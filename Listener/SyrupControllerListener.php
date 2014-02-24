<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Miro
 * Date: 23.11.12
 * Time: 15:09
 */
namespace Syrup\ComponentBundle\Listener;

use Monolog\Logger;
use Symfony\Component\BrowserKit\Request;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Syrup\ComponentBundle\Exception\UserException;

class SyrupControllerListener
{
	/**
	 * @var Logger
	 */
	protected $logger;

	protected $components;

	public function __construct(Logger $logger, array $components)
	{
		$this->logger = $logger;
		$this->components = $components;
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
		if (count($pathInfo) >= 3) {
			$componentName = $pathInfo[1];

			if (in_array($componentName, $this->components)) {
				$actionName = $pathInfo[2];

				if ($request->isMethod('POST') || $request->isMethod('PUT')) {
					$params = $request->getContent();
				} else {
					$params = $request->query->all();
				}

				$this->logger->info('Component ' . $componentName . ' finished action ' . $actionName, array(
					'method'    => $request->getMethod(),
					'params'    => $params
				));
			}
		}
	}
}
