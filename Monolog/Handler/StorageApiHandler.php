<?php
/**
 * @package syrup-component-bundle
 * @copyright 2015 Keboola
 * @author Jakub Matejka <jakub@keboola.com>
 */

namespace Syrup\ComponentBundle\Monolog\Handler;

use Keboola\StorageApi\Client;
use Keboola\StorageApi\Event;
use Monolog\Logger;
use Syrup\ComponentBundle\Exception\NoRequestException;
use Syrup\ComponentBundle\Exception\UserException;
use Syrup\ComponentBundle\Service\StorageApi\StorageApiService;

class StorageApiHandler extends \Monolog\Handler\AbstractHandler
{
    /**
     * @var \Keboola\StorageApi\Client
     */
    protected $storageApiClient;
    protected $appName;

    public function __construct($appName, StorageApiService $storageApiService)
    {
        $this->appName = $appName;
        try {
            $this->storageApiClient = $storageApiService->getClient();
        } catch (NoRequestException $e) {
            // Ignore when no SAPI client setup
        } catch (UserException $e) {
            // Ignore when no SAPI client setup
        }
    }

    public function setStorageApiClient(Client $client)
    {
        $this->storageApiClient = $client;
    }

    public function handle(array $record)
    {
        if (!$this->storageApiClient || $record['level'] == Logger::DEBUG) {
            return false;
        }

        $event = new Event();
        $event->setComponent($this->appName);
        $event->setMessage($record['message']);
        $event->setRunId($this->storageApiClient->getRunId());

        $params = [];
        if (isset($record['http'])) {
            $params['http'] = $record['http'];
        }
        $event->setParams($params);

        $results = [];
        if (isset($record['context']['exceptionId'])) {
            $results['exceptionId'] = $record['context']['exceptionId'];
        }
        if (isset($record['context']['job'])) {
            $results['job'] = $record['context']['job'];
        }
        $event->setResults($results);

        switch($record['level']) {
            case Logger::ERROR:
                $type = Event::TYPE_ERROR;
                break;
            case Logger::CRITICAL:
            case Logger::EMERGENCY:
            case Logger::ALERT:
                $type = Event::TYPE_ERROR;
                $event->setMessage("Application error");
                $event->setDescription("Contact support@keboola.com");
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
        $event->setType($type);

        $this->storageApiClient->createEvent($event);
    }
}
