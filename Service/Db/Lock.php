<?php
/**
 * Created by Miroslav Čillík <miro@keboola.com>
 * Date: 06/06/14
 * Time: 13:34
 */

namespace Syrup\ComponentBundle\Service\Db;

use Doctrine\DBAL\Connection;

class Lock
{
	/** @var Connection */
	protected $conn;

	protected $lockName;

	/**
	 * @param Connection $connection
	 * @param string $lockName Lock name is server wide - should be prefixed by db name
	 */
	public function __construct($connection, $lockName = '')
	{
		$this->conn = $connection;
		$this->setLockName($lockName);
	}

	/**
	 * @param int $timeout
	 * @return bool
	 */
	public function lock($timeout = 0)
	{
		$sql = 'SELECT GET_LOCK(:name, :timeout)';
		$sth = $this->conn->prepare($sql);
		$sth->execute(array(':name' => $this->prefixedLockName(), ':timeout' => $timeout));
		return $sth->fetchColumn();
	}

	public function isFree()
	{
		$sql = 'SELECT IS_FREE_LOCK(:name)';
		$sth = $this->conn->prepare($sql);
		$sth->execute(array(':name' => $this->prefixedLockName()));
		return $sth->fetchColumn();
	}

	public function unlock()
	{
		$sql = 'DO RELEASE_LOCK(:name)';
		$sth = $this->conn->prepare($sql);
		$sth->execute(array(':name' => $this->prefixedLockName()));
	}

	protected function prefixedLockName()
	{
		return $this->dbName() . '.' . $this->lockName;
	}

	protected function dbName()
	{
		$error = array();
		for ($i = 0; $i < 5; $i++) {
			$result = $this->conn->query('SELECT DATABASE()');
			if ($result) {
				return (string)$result->fetchColumn();
			} else {
				$error = $this->conn->errorInfo();
			}
			sleep($i * 60);
		}

		throw new \Exception('Could not connect to locking database. ' . implode(', ', $error));
	}

	public function getLockName()
	{
		return $this->lockName;
	}

	public function setLockName($lockName)
	{
		$this->lockName = $lockName;
	}
}
