<?php
/**
 * @author Miroslav Cillik <miro@keboola.com>
 * Date: 26.11.12
 * Time: 14:40
 */

namespace Syrup\ComponentBundle\Monolog\Formatter;

use Monolog\Formatter\JsonFormatter;
use Monolog\Logger;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Syrup\ComponentBundle\Monolog\Uploader\SyrupS3Uploader;
use Keboola\StorageApi\Client;
use Keboola\StorageApi\Event;

class SyrupJsonFormatter extends JsonFormatter
{
	protected $_appName;
	protected $_runId = '';
	protected $_componentName = '';

	/**
	 * @var Client
	 */
	protected $_sapi;

	/**
	 * @var SyrupS3Uploader
	 */
	protected $_uploader;

	public function __construct($appName, $uploader)
	{
		$this->_appName = $appName;
		$this->_uploader = $uploader;
	}

	public function setComponentName($name)
	{
		$this->_componentName = $name;
	}

	public function getComponentName()
	{
		return $this->_componentName;
	}

	public function getAppName()
	{
		return $this->_appName;
	}

	public function setRunId($id)
	{
		$this->_runId = $id;
	}

	public function getRunId()
	{
		return $this->_runId;
	}

	public function setStorageApiClient($client)
	{
		$this->_sapi = $client;
	}

	/**
	 * {@inheritdoc}
	 */
	public function format(array $record)
	{
		$record['app']          = $this->_appName;
		$record['component']    = $this->_componentName;
		$record['priority']     = $record['level_name'];
		$record['pid']          = getmypid();
		$record['runId']        = $this->_runId;

		switch($record['level']) {
			case Logger::ERROR:
				$record['error'] = 'User error';
				break;
			case Logger::CRITICAL:
				$record['error'] = 'Application error';
				break;
		}

		if ($this->_sapi != null) {
			$record['user'] = $this->_sapi->getLogData();
		}

		$e = null;

		// Upload exception trace to S3
		if (isset($record['context']['exception'])) {
			/** @var \Exception $e */
			$e = $record['context']['exception'];
			unset($record['context']['exception']);
			if ($e instanceof \Exception) {
				$serialized = $e->__toString();
				$record['attachment'] = $this->_uploader->uploadString('exception', $serialized);
			}
		}

		if (isset($record['context']['exceptionId'])) {
			$record['exceptionId'] = $record['context']['exceptionId'];
			unset($record['context']['exceptionId']);
		}

		// Log to SAPI events
		if ($record['level'] != Logger::DEBUG
			&& $record['level'] != Logger::CRITICAL
			&& $this->_sapi != null) {
			$this->_logToSapi($record, $e);
		}

		unset($record['level_name']);

		return json_encode($record);
	}

	/**
	 * {@inheritdoc}
	 */
	public function formatBatch(array $records)
	{
		$newRecords = array();
		foreach($records as $record) {
			$newRecords[] = json_decode($this->format($record), true);
		}
		return json_encode($records);
	}

	protected function _logToSapi($record, $e)
	{
		$sapiEvent = new Event();
		$sapiEvent->setComponent($this->_componentName);
		$sapiEvent->setMessage($record['message']);
		$sapiEvent->setRunId($this->_runId);

		if ($e != null) {
			$sapiEvent->setDescription($e->getMessage());
		}

		if (isset($record['exceptionId'])) {
			$sapiEvent->setResults(array(
				'exceptionId' => $record['exceptionId']
			));
		}

		switch($record['level']) {
			case Logger::ERROR:
			case Logger::CRITICAL:
			case Logger::EMERGENCY:
			case Logger::ALERT:
				$type = Event::TYPE_ERROR;
				break;
			case Logger::WARNING:
			case Logger::NOTICE:
				$type = Event::TYPE_WARN;
				break;
			case Logger::INFO:
			default:
				$type = Event::TYPE_INFO;
				break;
		}

		$sapiEvent->setType($type);
		$this->_sapi->createEvent($sapiEvent);
	}
}
