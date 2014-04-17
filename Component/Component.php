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
use Syrup\ComponentBundle\Filesystem\TempService;
use Syrup\ComponentBundle\Service\Queue\Queue;

class Component implements ComponentInterface
{
	/**
	 * @var ContainerInterface
	 */
	public $_container;

	/**
	 * @var \Keboola\StorageApi\Client
	 */
	protected $_storageApi;

	/**
	 * @var \Monolog\Logger
	 */
	protected $_log;

	/**
	 * @var Connection
	 */
	protected $_db;

	/**
	 * @var string
	 */
	protected $_name = 'componentName';

	/**
	 * @var string
	 */
	protected $_prefix = '';

	/**
	 * @var array
	 */
	protected $_results;

    /**
     * @var TempService
     */
    private $_temp;

	/**
	 * @var Client
	 */
	protected $_sharedSapi;

	/**
	 * @var AesEncryptor
	 */
	protected $_encryptor;

	/**
	 * @var Queue
	 */
	protected $_queue;

    /**
	 * @param \Keboola\StorageApi\Client $storageApi
	 * @param \Monolog\Logger $log
	 */
	public function __construct(Client $storageApi, $log)
	{
		$this->_storageApi = $storageApi;
		$this->_log = $log;
		Reader::$client = $this->_storageApi;
	}

	/**
	 * @param Connection $db
	 * @return $this
	 */
	public function setConnection($db)
	{
		$this->_db = $db;

		return $this;
	}

	/**
	 * @param ContainerInterface $container
	 * @return $this
	 */
	public function setContainer($container)
	{
		$this->_container = $container;

		return $this;
	}

	/**
	 * @param Client $sharedSapi
	 * @return $this
	 */
	public function setSharedSapi(Client $sharedSapi)
	{
		$this->_sharedSapi = $sharedSapi;

		return $this;
	}

	public function setEncryptor(AesEncryptor $encryptor)
	{
		$this->_encryptor = $encryptor;
	}

	/**
	 * @param null $params - parameters passed from API call
	 * @return mixed
	 */
	public function postRun($params)
	{
		$config = $this->getConfig();

		// $result should be instance of Table or array of Table objects
		$response = $this->_process($config, $params);

		if (!empty($this->_results)) {
			foreach ($this->_results as $table) {
				$this->_saveTable($table);
			}
		}

		return $response;
	}

	/**
	 * Override this - get data and process them
	 */
	protected function _process($config, $params)
	{
		return false;
	}

	protected function _saveTable($table)
	{
		if ($table instanceof Table) {
			$table->save();
		} else {
			throw new \Exception("Result must be instance of Keboola\\StorageApi\\Table or array of these instances.");
		}
	}

	public function getResults()
	{
		return $this->_results;
	}

	/**
	 * Reads configuration from StorageApi
	 *
	 * could be empty - extractor with no configuration
	 */
	public function getConfig()
	{
		if ($this->_storageApi->bucketExists('sys.c-' . $this->getFullName())) {
			return Reader::read('sys.c-' . $this->getFullName());
		} else {
			return array();
		}
	}

	public function getFullName()
	{
		return $this->_prefix . '-' . $this->_name;
	}

	/**
	 * @return TempService
	 */
	protected function getTemp()
    {
        if ($this->_temp == null) {
            $this->_temp = $this->_container->get('syrup.temp_service');
        }

        return $this->_temp;
    }


}
