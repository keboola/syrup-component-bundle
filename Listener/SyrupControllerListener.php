<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Miro
 * Date: 23.11.12
 * Time: 15:09
 */
namespace Syrup\ComponentBundle\Listener;

use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class SyrupControllerListener
{
	public function onKernelController(FilterControllerEvent $event)
	{
		if (HttpKernelInterface::MASTER_REQUEST === $event->getRequestType()) {
			$controllers = $event->getController();
			if(is_array($controllers)) {
				$controller = $controllers[0];

				if(is_object($controller) && method_exists($controller, 'preExecute')) {
					$controller->preExecute();
				}
			}
		}
	}
}
