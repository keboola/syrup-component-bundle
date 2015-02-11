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
class SyslogProcessor
{

    private $componentName;
    private $tokenData;
    private $runId;

    /**
     * @var Uploader
     */
    private $s3Uploader;

    public function __construct($componentName, StorageApiService $storageApiService, Uploader $s3Uploader)
    {
        $this->componentName = $componentName;
        $this->s3Uploader = $s3Uploader;
        try {
            // does not work for some commands
            $storageApiClient = $storageApiService->getClient();
            $this->tokenData = $storageApiClient->getLogData();
            $this->runId = $storageApiClient->getRunId();
        } catch (SyrupComponentException $e) {
        }
    }

    public function setRunId($runId)
    {
        $this->runId = $runId;
    }

    public function setTokenData($tokenData)
    {
        $this->tokenData = $tokenData;
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
        if (isset($record['message']) && strlen($record['message'])>1024) {
            $record['message'] = $this->s3Uploader->uploadString('message', $record['message']);
        }
        $record['component'] = $this->componentName;
        $record['runId'] = $this->runId;
        $record['pid'] = getmypid();
        $record['priority'] = $record['level_name'];

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

        if (isset($record['context']['data']) && !count($record['context']['data'])) {
            unset($record['context']['data']);
        }
        if (!count($record['extra'])) {
            unset($record['extra']);
        }
        if (!count($record['context'])) {
            unset($record['context']);
        } else {
            $json = json_encode($record['context']);
            if (strlen($json) > 1024) {
                $record['context'] = $this->s3Uploader->uploadString('context', $json, 'text/json');
            }
        }

        return $record;
    }
}
