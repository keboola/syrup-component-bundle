<?php

/**
 * @author Miroslav Cillik <miro@keboola.com>
 * Date: 23.11.12
 * Time: 17:04
 */

namespace Syrup\ComponentBundle\Component;

use Keboola\Encryption\AesEncryptor;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Keboola\StorageApi\Client;
use Keboola\StorageApi\Config\Reader;
use Keboola\StorageApi\Table;
use Doctrine\DBAL\Connection;
use Syrup\ComponentBundle\Filesystem\Temp;
use Syrup\ComponentBundle\Filesystem\TempService;

class Component implements ComponentInterface
{
	/**
	 * @var ContainerInterface
	 */
	public $container;

	/**
	 * @var \Keboola\StorageApi\Client
	 */
	protected $storageApi;

	/**
	 * @var \Monolog\Logger
	 */
	protected $log;

	/**
	 * @var Connection
	 */
	protected $db;

	/**
	 * @var string
	 */
	protected $name = 'componentName';

	/**
	 * @deprecated
	 * @var
	 */
	protected $results;

	/**
	 * @var string
	 */
	protected $prefix = '';

	/**
	 * @var Temp
	 */
	private $temp;

	/**
	 * @var Client
	 */
	protected $sharedSapi;

	/**
	 * @var AesEncryptor
	 */
	protected $encryptor;

	/**
	 * @param \Keboola\StorageApi\Client $storageApi
	 * @param \Monolog\Logger $log
	 */
	public function __construct(Client $storageApi, $log)
	{
		$this->storageApi = $storageApi;
		$this->log = $log;
		Reader::$client = $this->storageApi;
	}

	/**
	 * @param Connection $db
	 * @return $this
	 */
	public function setConnection($db)
	{
		$this->db = $db;

		return $this;
	}

	/**
	 * @param ContainerInterface $container
	 * @return $this
	 */
	public function setContainer($container)
	{
		$this->container = $container;

		return $this;
	}

	/**
	 * @param Client $sharedSapi
	 * @return $this
	 */
	public function setSharedSapi(Client $sharedSapi)
	{
		$this->sharedSapi = $sharedSapi;

		return $this;
	}

	public function setEncryptor(AesEncryptor $encryptor)
	{
		$this->encryptor = $encryptor;
	}

	/**
	 * @param null $params - parameters passed from API call
	 * @return mixed
	 * @deprecated
	 */
	public function postRun($params)
	{
		$config = $this->getConfig();

		// $result should be instance of Table or array of Table objects
		$response = $this->process($config, $params);

		if (!empty($this->results)) {
			foreach ($this->results as $table) {
				$this->saveTable($table);
			}
		}

		return $response;
	}

	/**
	 * Override this - get data and process them
	 * @deprecated
	 */
	protected function process($config, $params)
	{
		return false;
	}

	/**
	 * @param $table
	 * @deprecated
	 * @throws \Exception
	 */
	protected function saveTable($table)
	{
		if ($table instanceof Table) {
			$table->save();
		} else {
			throw new \Exception("Result must be instance of Keboola\\StorageApi\\Table or array of these instances.");
		}
	}

	/**
	 * @deprecated
	 * @return mixed
	 */
	public function getResults()
	{
		return $this->results;
	}

	/**
	 * Reads configuration from StorageApi
	 *
	 * could be empty - extractor with no configuration
	 */
	public function getConfig()
	{
		if ($this->storageApi->bucketExists('sys.c-' . $this->getFullName())) {
			return Reader::read('sys.c-' . $this->getFullName());
		} else {
			return array();
		}
	}

	public function getFullName()
	{
		return $this->prefix . '-' . $this->name;
	}

	/**
	 * @return Temp
	 */
	protected function getTemp()
	{
		if ($this->temp == null) {
			$this->temp = $this->container->get('syrup.temp');
		}

		return $this->temp;
	}


}
