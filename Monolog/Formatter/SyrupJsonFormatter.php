<?php
/**
 * @author Miroslav Cillik <miro@keboola.com>
 * Date: 26.11.12
 * Time: 14:40
 */

namespace Syrup\ComponentBundle\Monolog\Formatter;

use Monolog\Logger;
use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\Debug\ExceptionHandler;
use Syrup\ComponentBundle\Exception\SyrupComponentException;
use Syrup\ComponentBundle\Job\Metadata\JobInterface;
use Syrup\ComponentBundle\Monolog\Uploader\SyrupS3Uploader;
use Keboola\StorageApi\Client;
use Keboola\StorageApi\Event;
use Syrup\ComponentBundle\Service\StorageApi\StorageApiService;

/**
 * Class SyrupJsonFormatter
 * @package Syrup\ComponentBundle\Monolog\Formatter
 * @deprecated
 */
class SyrupJsonFormatter extends \Monolog\Formatter\JsonFormatter
{
	protected $appName;

	/** @var Client */
	protected $storageApi;

	/** @var StorageApiService */
	protected $storageApiService;

	/** @var SyrupS3Uploader */
	protected $uploader;

	/** @var JobInterface */
	protected $job;

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

	public function getAppName()
	{
		return $this->appName;
	}

	public function getRunId()
	{
		if ($this->storageApi == null) {
			return 'not set';
		}
		return $this->storageApi->getRunId();
	}

	public function setJob(JobInterface $job)
	{
		$this->job = $job;
	}

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

		$record['component']    = $this->appName;
		$record['priority']     = $record['level_name'];
		$record['pid']          = getmypid();
		$record['runId']        = $this->getRunId();

		switch($record['level']) {
			case Logger::ERROR:
				$record['error'] = 'User error';
				break;
			case Logger::CRITICAL:
			case Logger::ALERT:
			case Logger::EMERGENCY:
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
				$flattenException = FlattenException::create($e);
				$eHandler = new ExceptionHandler(true, 'UTF-8');
				$serialized = $eHandler->getContent($flattenException);

				$record['attachment'] = $this->uploader->uploadString('exception', $serialized, 'text/html');
			}
		}

		// exception id
		if (isset($record['context']['exceptionId'])) {
			$record['exceptionId'] = $record['context']['exceptionId'];
			unset($record['context']['exceptionId']);
		}

		// requeste data
		if (isset($record['context']['request'])) {
			$record['request'] = $record['context']['request'];
			unset($record['context']['request']);
		}

		// job data
		if ($this->job != null) {
			$record['job'] = $this->job->getLogData();
		}

		// Log to SAPI events
        if (
			$this->storageApi != null
			&& $this->appName != null
			&& $record['level'] != Logger::DEBUG
			&& $record['channel'] != 'event'
			&& $record['channel'] != 'request'
			&& $record['channel'] != 'doctrine'
		) {
			$this->logToSapi($record);
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

	protected function logToSapi($record)
	{
		$sapiEvent = new Event();
		$sapiEvent->setComponent($this->appName);
        $sapiEvent->setMessage($record['message']);
		$sapiEvent->setRunId($this->storageApi->getRunId());
		$sapiEvent->setParams($record['context']);

		$results = [];
		if (isset($record['exceptionId'])) {
			$results['exceptionId'] = $record['exceptionId'];
		}
		if (isset($record['request'])) {
			$results['request'] = $record['request'];
		}
		if (isset($record['job'])) {
			$results['job'] = $record['job'];
		}
		$sapiEvent->setResults($results);

		switch($record['level']) {
			case Logger::ERROR:
				$type = Event::TYPE_ERROR;
				break;
			case Logger::CRITICAL:
			case Logger::EMERGENCY:
			case Logger::ALERT:
				$type = Event::TYPE_ERROR;
				$sapiEvent->setMessage("Application error");
				$sapiEvent->setDescription("Contact support@keboola.com");
				$sapiEvent->setParams([]);
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
