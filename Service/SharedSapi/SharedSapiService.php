<?php
/**
 * Created by Miroslav Čillík <miro@keboola.com>
 * Date: 02/12/13
 * Time: 16:16
 */

namespace Syrup\ComponentBundle\Service\SharedSapi;

use Keboola\StorageApi\Client;
use Keboola\StorageApi\Table;

/**
 * Class SharedSapiService
 * @package Syrup\ComponentBundle\Service\SharedSapi
 * @deprecated
 */
class SharedSapiService
{
    /** @var Client */
    protected $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function log(Event $event)
    {
        if ($event->getId() == null) {
            $event->setId($this->client->generateId());
        }
        $table = new Table($this->client, 'in.c-syrup.' . $event->getTable(), '', 'id');
        $table->setHeader($event->getHeader());
        $table->setFromArray(array($event->toArray()));
        $table->setIncremental(true);
        $table->setPartial(true);

        $table->save();
    }
}
