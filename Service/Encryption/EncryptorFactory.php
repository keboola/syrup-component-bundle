<?php
/**
 * Created by Miroslav Čillík <miro@keboola.com>
 * Date: 21/01/14
 * Time: 11:52
 */

namespace Syrup\ComponentBundle\Service\Encryption;


use Syrup\ComponentBundle\Exception\ApplicationException;
use Syrup\ComponentBundle\Exception\SyrupComponentException;

class EncryptorFactory
{
	protected $componentsConfig;

	public function __construct(array $componentsConfig)
	{
		$this->componentsConfig = $componentsConfig;
	}

	public function get($componentName)
	{
		$config = $this->componentsConfig[$componentName];

		if (!isset($config['encryption_key'])) {
			throw new ApplicationException("Encryption Key was not set in configuration for component '" . $componentName . "'");
		}

		return new Encryptor($config['encryption_key']);
	}

} 