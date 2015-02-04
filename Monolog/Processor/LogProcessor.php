<?php

namespace Syrup\ComponentBundle\Monolog\Processor;

use Monolog\Logger;
use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\Debug\ExceptionHandler;
use Syrup\ComponentBundle\Aws\S3\Uploader;
use Syrup\ComponentBundle\Exception\SyrupComponentException;
use Syrup\ComponentBundle\Job\Metadata\JobInterface;
use Syrup\ComponentBundle\Service\StorageApi\StorageApiService;

/**
 * Injects info about component and used Storage Api token
 */
class LogProcessor
{

	private $componentName;
	private $tokenData;
	private $runId;
	/**
	 * @var JobInterface
	 */
	private $job;
	/**
	 * @var Uploader
	 */
	private $s3Uploader;

	public function __construct($componentName, StorageApiService $storageApiService, Uploader $s3Uploader)
	{
		$this->componentName = $componentName;
		$this->s3Uploader = $s3Uploader;
		try {
			// does not work for commands
			// @TODO manually set SAPI client to StorageApiService in command
			$storageApiClient = $storageApiService->getClient();
			$this->tokenData = $storageApiClient->getLogData();
			$this->runId = $storageApiClient->getRunId();
		} catch (SyrupComponentException $e) {
		}
	}

	public function setJob(JobInterface $job)
	{
		$this->job = $job;
	}

	/**
	 * @param  array $record
	 * @return array
	 */
	public function __invoke(array $record)
	{
		return $this->processRecord($record);
	}

	public function processRecord(array $record)
	{
		$record['component'] = $this->componentName;
		$record['runId'] = $this->runId;
		$record['pid'] = getmypid();
		$record['priority'] = $record['level_name'];

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

		if ($this->tokenData) {
			$record['token'] = [
				'id' => $this->tokenData['id'],
				'description' => $this->tokenData['description'],
				'token' => $this->tokenData['token'],
				'owner' => [
					'id' => $this->tokenData['owner']['id'],
					'name' => $this->tokenData['owner']['name']
				]
			];
		}

		if (isset($record['context']['exceptionId'])) {
			$record['exceptionId'] = $record['context']['exceptionId'];
			unset($record['context']['exceptionId']);
		}
		if (isset($record['context']['exception'])) {
			/** @var \Exception $e */
			$e = $record['context']['exception'];
			unset($record['context']['exception']);
			if ($e instanceof \Exception) {
				$flattenException = FlattenException::create($e);
				$eHandler = new ExceptionHandler(true, 'UTF-8');
				$serialized = $eHandler->getContent($flattenException);

				$record['exception'] = [
					'class' => get_class($e),
					'message' => $e->getMessage(),
					'code' => $e->getCode(),
					'attachment' => $this->s3Uploader->uploadString('exception', $serialized, 'text/html')
				];
			}
		}

		if (!count($record['context'])) {
			unset($record['context']);
		}
		if (!count($record['extra'])) {
			unset($record['extra']);
		}

		if ($this->job) {
			$record['job'] = $this->job->getLogData();
		}

		return $record;
	}

}
