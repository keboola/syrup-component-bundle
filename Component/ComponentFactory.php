<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Miro
 * Date: 26.11.12
 * Time: 11:02
 * To change this template use File | Settings | File Templates.
 */

namespace Syrup\ComponentBundle\Component;

use Keboola\StorageApi\Client;
use Monolog\Logger;
use Doctrine\Bundle\DoctrineBundle\Registry;

class ComponentFactory
{
	/**
	 * @var \Monolog\Logger
	 */
	protected $_logger;

	/**
	 * @var array
	 */
	protected $_componentsConfig;

	/**
	 * @var Registry
	 */
	protected $_dbal;

	public function __construct(Logger $logger, Registry $dbal, array $componentsConfig)
	{
		$this->_logger = $logger;
		$this->_componentsConfig = $componentsConfig;
		$this->_dbal = $dbal;
	}

	/**
	 * @param \Keboola\StorageApi\Client $storageApi
	 * @param $componentName
	 * @return ComponentInterface $component
	 * @throws \Exception
	 */
	public function get(Client $storageApi, $componentName)
	{
		if (isset($this->_componentsConfig[strtolower($componentName)])) {

			$componentConfig = $this->_componentsConfig[strtolower($componentName)];

			if (isset($componentConfig['class'])) {
				$className = $componentConfig['class'];
				if (class_exists($componentConfig['class'])) {
					/** @var Component $component */
					$component = new $className($storageApi, $this->_logger);

					if (isset($componentConfig['db'])) {
						$component->setConnection($this->_dbal->getConnection($componentConfig['db']));
					}

					// Shared Config for component
					if (isset($componentConfig['shared_sapi']['token'])) {
						$token = $componentConfig['shared_sapi']['token'];
						$url = null;
						if (isset($componentConfig['shared_sapi']['url'])) {
							$url = $componentConfig['shared_sapi']['url'];
						}
						$component->setSharedSapi(new Client($token, $url, $componentName));
					}

					return $component;

				} else {
					$error = 'Component class "'.$componentConfig['class'].'" does not exists';
				}
			} else {
				$error = 'Missing component class definition in configuration.';
			}
		} else {
			$error = 'Missing configuration or wrong component name';
		}

		throw new \Exception('Failed to load component. ' . $error);
	}

}
