<?php
/**
 * Created by Miroslav Čillík <miro@keboola.com>
 * Date: 02/07/14
 * Time: 14:53
 */

namespace Syrup\ComponentBundle\Service\Queue;


use Doctrine\DBAL\Connection;
use Syrup\ComponentBundle\Exception\ApplicationException;

class QueueFactory
{
	protected $db;

	protected $dbTable;

	public function __construct(Connection $db, $queueParams)
	{
		$this->db = $db;
		$this->dbTable = $queueParams['db_table'];
	}

	public function get($name)
	{
		$sql = "SELECT access_key, secret_key, region, url FROM {$this->dbTable} WHERE id = '{$name}'";
		$queueConfig = $this->db->query($sql)->fetch();

		if (!$queueConfig) {
			throw new ApplicationException('No queue configuration found in DB.');
		}

		return new QueueService($queueConfig);
	}
}
