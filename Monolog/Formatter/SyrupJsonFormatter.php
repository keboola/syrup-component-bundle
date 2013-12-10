<?php
/**
 * @author Miroslav Cillik <miro@keboola.com>
 * Date: 26.11.12
 * Time: 14:40
 */

namespace Syrup\ComponentBundle\Monolog\Formatter;

use Monolog\Formatter\JsonFormatter;
use Monolog\Logger;
use Syrup\ComponentBundle\Exception\NoRequestException;
use Syrup\ComponentBundle\Exception\SyrupComponentException;
use Syrup\ComponentBundle\Monolog\Uploader\SyrupS3Uploader;
use Keboola\StorageApi\Client;
use Keboola\StorageApi\Event;
use Syrup\ComponentBundle\Service\StorageApi\StorageApiService;

class SyrupJsonFormatter extends JsonFormatter
{
	protected $appName;

	protected $componentName = '';

	/** @deprecated - will be removed in 1.4.0 */
	protected $runId;

	/** @var Client */
	protected $storageApi;

	/** @var StorageApiService */
	protected $storageApiService;

	/** @var SyrupS3Uploader */
	protected $uploader;

	/**
	 * @param String            $appName
	 * @param SyrupS3Uploader   $uploader
	 * @param StorageApiService $storageApiService
	 */
	public function __construct($appName, SyrupS3Uploader $uploader, StorageApiService $storageApiService)
	{
		$this->appName = $appName;
		$this->uploader = $uploader;
		$this->storageApiService = $storageApiService;
	}

	public function setComponentName($name)
	{
		$this->componentName = $name;
	}

	public function getComponentName()
	{
		return $this->componentName;
	}

	public function getAppName()
	{
		return $this->appName;
	}

	/** @deprecated - will be removed in 1.4.0 */
	public function setRunId($id)
	{
		$this->runId = $id;
	}

	public function getRunId()
	{
		if ($this->storageApi == null) {
			return 'not set';
		}
		return $this->storageApi->getRunId();
	}

	/** @deprecated - will be removed in 1.4.0 - set SAPI client in constructor */
	public function setStorageApiClient(Client $storageApi)
	{
		$this->storageApi = $storageApi;
	}

	/**
	 * {@inheritdoc}
	 */
	public function format(array $record)
	{
		if ($this->storageApi == null) {
			try {
				$this->storageApi = $this->storageApiService->getClient();
			} catch (SyrupComponentException $e) {

			}
		}

		$record['app']          = $this->appName;
		$record['component']    = $this->componentName;
		$record['priority']     = $record['level_name'];
		$record['pid']          = getmypid();
		$record['runId']        = $this->getRunId();

		switch($record['level']) {
			case Logger::ERROR:
				$record['error'] = 'User error';
				break;
			case Logger::CRITICAL:
				$record['error'] = 'Application error';
				break;
		}

		if ($this->storageApi != null) {
			$record['user'] = $this->storageApi->getLogData();
		}

		$e = null;

		// Upload exception trace to S3
		if (isset($record['context']['exception'])) {
			/** @var \Exception $e */
			$e = $record['context']['exception'];
			unset($record['context']['exception']);
			if ($e instanceof \Exception) {
				$serialized = $e->__toString();
				$record['attachment'] = $this->uploader->uploadString('exception', $serialized);
			}
		}

		if (isset($record['context']['exceptionId'])) {
			$record['exceptionId'] = $record['context']['exceptionId'];
			unset($record['context']['exceptionId']);
		}

		// Log to SAPI events
		if (
			$record['level'] != Logger::DEBUG
			&& $record['level'] != Logger::CRITICAL
			&& $this->storageApi != null
			&& $this->componentName != null
		) {
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

	protected function _logToSapi($record, $e = null)
	{
		$sapiEvent = new Event();
		$sapiEvent->setComponent($this->componentName);
		$sapiEvent->setMessage($record['message']);
		$sapiEvent->setRunId($this->storageApi->getRunId());

		if ($e instanceof \Exception) {
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
		$this->storageApi->createEvent($sapiEvent);
	}
}
