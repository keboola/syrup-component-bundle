<?php
/**
 * @author Miroslav Cillik <miro@keboola.com>
 * Date: 26.11.12
 * Time: 14:40
 */

namespace Syrup\ComponentBundle\Monolog\Formatter;

use Monolog\Formatter\JsonFormatter;
use Monolog\Logger;
use Syrup\ComponentBundle\Monolog\Uploader\SyrupS3Uploader;

class SyrupJsonFormatter extends JsonFormatter
{
	protected $_appName;
	protected $_runId = '';
	protected $_exceptionId;
	protected $_componentName = '';
	protected $_logData;

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

	public function setLogData($logData)
	{
		$this->_logData = $logData;
	}

	/**
	 * {@inheritdoc}
	 */
	public function format(array $record)
	{
		$record['app']          = $this->_appName;
		$record['component']    = $this->_componentName;
		$record['priority']     = $record['level_name'];
		$record['user']         = $this->_logData;
		$record['pid']          = getmypid();
		$record['runId']        = $this->_runId;

		if ($record['level_name'] == Logger::ERROR) {
			$record['error'] = 'Application error';
		}

		if (isset($record['context']['exception'])) {
			$e = $record['context']['exception'];
			unset($record['context']['exception']);
			$serialized = var_export(json_encode((array) $e), true);
			$record['attachment'] = $this->_uploader->uploadString('exception', $serialized);
		}

		if (isset($record['context']['exceptionId'])) {
			$record['exceptionId']  = $this->_exceptionId;
			unset($record['context']['exceptionId']);
		}

		unset($record['level_name']);
		unset($record['level']);
		unset($record['channel']);

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
		return json_encode($newRecords);
	}
}
