<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Miro
 * Date: 26.11.12
 * Time: 11:02
 * To change this template use File | Settings | File Templates.
 */

namespace Syrup\ComponentBundle\Component;

use Keboola\Encryption\AesEncryptor;
use Keboola\StorageApi\Client;
use Monolog\Logger;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Syrup\ComponentBundle\Exception\ApplicationException;
use Syrup\ComponentBundle\Exception\UserException;

class ComponentFactory
{
	/**
	 * @var \Monolog\Logger
	 */
	protected $logger;

	/**
	 * @var array
	 */
	protected $componentsConfig;

	/**
	 * @var Registry
	 */
	protected $dbal;

	public function __construct(Logger $logger, Registry $dbal, array $componentsConfig)
	{
		$this->logger = $logger;
		$this->componentsConfig = $componentsConfig;
		$this->dbal = $dbal;
	}

	/**
	 * @param \Keboola\StorageApi\Client $storageApi
	 * @param $componentName
	 * @return ComponentInterface $component
	 * @throws \Exception
	 */
	public function get(Client $storageApi, $componentName)
	{
		if (!isset($this->componentsConfig[strtolower($componentName)])) {
			$error = "Component '" . $componentName . "' could not be found.";
			throw new UserException('Failed to load component. ' . $error);
		}

		$componentConfig = $this->componentsConfig[strtolower($componentName)];

		if (!isset($componentConfig['class'])) {
			$error = 'Missing component class definition in configuration.';
			throw new ApplicationException('Failed to load component. ' . $error);
		}

		$className = $componentConfig['class'];

		if (!class_exists($className)) {
			$error = 'Component class "' . $className . '" does not exists';
			throw new ApplicationException('Failed to load component. ' . $error);
		}

		/** @var Component $component */
		$component = new $className($storageApi, $this->logger);

		// DB
		if (isset($componentConfig['db'])) {
			$component->setConnection($this->dbal->getConnection($componentConfig['db']));
		}

		// Shared Config
		if (isset($componentConfig['shared_sapi']['token'])) {
			$token = $componentConfig['shared_sapi']['token'];
			$url = null;
			if (isset($componentConfig['shared_sapi']['url'])) {
				$url = $componentConfig['shared_sapi']['url'];
			}
			$component->setSharedSapi(new Client($token, $url, $componentName));
		}

		// Encryption
		if (isset($componentConfig['encryption_key'])) {
			$component->setEncryptor(new AesEncryptor($componentConfig['encryption_key']));
		}

		return $component;
	}

}
