<?php

/**
 * @author Miroslav Cillik <miro@keboola.com>
 * Date: 23.11.12
 * Time: 17:04
 */

namespace Syrup\ComponentBundle\Component;

use Syrup\ComponentBundle\Component\ComponentInterface;
use Keboola\StorageApi\Client;
use Keboola\StorageApi\Config\Reader;
use Keboola\StorageApi\Table;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Response;

class Component implements ComponentInterface
{
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
	 */
	public function setConnection($db)
	{
		$this->_db = $db;
	}

	/**
	 * @param null $params - parameters passed from API call
	 */
	public function run($params = null)
	{
		$this->_log->debug("Component " . $this->_prefix . "-" . $this->_name . " started.");
		$timestart = microtime(true);

		$config = $this->getConfig();

		// $result should be instance of Table or array of Table objects
		$this->_process($config, $params);

		if (!empty($this->_results)) {
			foreach ($this->_results as $table) {
				$this->_saveTable($table);
			}
		}

		$duration = microtime(true) - $timestart;
		$this->_log->info("Component: " . $this->_name . " finished. Duration: " . $duration);
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
		if ($this->_storageApi->bucketExists('sys.c-' . $this->_prefix . '-' . $this->_name)) {
			return Reader::read('sys.c-' . $this->_prefix . '-' . $this->_name);
		} else {
			return array();
		}
	}
}
