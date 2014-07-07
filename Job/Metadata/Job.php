<?php
/**
 * Created by Miroslav Čillík <miro@keboola.com>
 * Date: 28/05/14
 * Time: 14:37
 */

namespace Syrup\ComponentBundle\Job\Metadata;


use Syrup\ComponentBundle\Exception\ApplicationException;
use Syrup\ComponentBundle\Exception\UserException;

class Job implements JobInterface
{
	const STATUS_WAITING    = 'waiting';
	const STATUS_PROCESSING = 'processing';
	const STATUS_SUCCESS    = 'success';
	const STATUS_CANCELLED  = 'cancelled';
	const STATUS_ERROR      = 'error';

	protected $data = [
		'id'        => null,
		'runId'     => null,
		'lockName'  => null,
		'project'   => [
			'id'        => null,
			'name'      => null
		],
		'token'     => [
			'id'            => null,
			'description'   => null,
			'token'         => null
		],
		'component' => null,
		'command'   => null,
		'params'    => [],
		'result'    => [],
		'status'    => null,
		'process'   => [
			'host'      => null,
			'pid'       => null
		],
		'createdTime'   => null,
		'startTime'     => null,
		'endTime'       => null,
		'durationSeconds'   => null,
		'waitSeconds'       => null
	];

	public function __construct(array $data = [])
	{
		$this->data['status'] = self::STATUS_WAITING;
		$this->setLockName($this->getComponent() . '-' . $this->getProject()['id']);
		$this->data = array_merge($this->data, $data);
	}

	public function getId()
	{
		return $this->data['id'];
	}

	public function setId($id)
	{
		$this->data['id'] = $id;
		return $this;
	}

	public function getProject()
	{
		return $this->data['project'];
	}

	/**
	 * @param array $project
	 * - id
	 * - name
	 * @return $this
	 */
	public function setProject(array $project)
	{
		if (!isset($project['id'])) {
			throw new ApplicationException("Missing project id");
		}

		if (!isset($project['name'])) {
			throw new ApplicationException("Missing project name");
		}

		$this->data['project'] = $project;
		return $this;
	}

	public function getToken()
	{
		return $this->data['token'];
	}

	public function setToken(array $token)
	{
		if (!isset($token['id'])) {
			throw new ApplicationException("Missing token id");
		}

		if (!isset($token['description'])) {
			throw new ApplicationException("Missing token description");
		}

		if (!isset($token['token'])) {
			throw new ApplicationException("Missing token");
		}

		$this->data['token'] = $token;
	}

	public function getCommand()
	{
		return $this->data['command'];
	}

	public function setCommand($cmd)
	{
		$this->data['command'] = $cmd;
		return $this;
	}

	public function getStatus()
	{
		return $this->data['status'];
	}

	public function setStatus($status)
	{
		$this->data['status'] = $status;
		return $this;
	}

	public function getComponent()
	{
		return $this->data['component'];
	}

	public function setComponent($component)
	{
		$this->data['component'] = $component;
		return $this;
	}

	public function setResult($result)
	{
		$this->data['result'] = $result;
		return $this;
	}

	public function getResult()
	{
		return $this->data['result'];
	}

	public function getRunId()
	{
		return $this->data['runId'];
	}

	public function setRunId($runId)
	{
		$this->data['runId'] = $runId;
	}

	public function setLockName($lockName)
	{
		$this->data['lockName'] = $lockName;
	}

	public function getLockName()
	{
		return $this->data['lockName'];
	}

	public function setParams(array $params)
	{
		$this->data['params'] = $params;
	}

	public function getParams()
	{
		return $this->data['params'];
	}

	public function getProcess()
	{
		return $this->data['process'];
	}

	public function setProcess(array $process)
	{
		if (!isset($process['host'])) {
			throw new ApplicationException("Missing process host");
		}

		if (!isset($process['pid'])) {
			throw new ApplicationException("Missing process pid");
		}

		$this->data['process'] = $process;

		return $this;
	}

	public function getCreatedTime()
	{
		return $this->data['createdTime'];
	}

	public function setCreatedTime($datetime)
	{
		$this->data['createdTime'] = $datetime;
	}

	public function getStartTime()
	{
		return $this->data['startTime'];
	}

	public function setStartTime($datetime)
	{
		$this->data['startTime'] = $datetime;
	}

	public function getEndTime()
	{
		return $this->data['endTime'];
	}

	public function setEndTime($datetime)
	{
		$this->data['endTime'] = $datetime;
	}

	public function getDurationSeconds()
	{
		return $this->data['durationSeconds'];
	}

	public function setDurationSeconds($seconds)
	{
		$this->data['durationSeconds'] = $seconds;
	}

	public function getWaitSeconds()
	{
		return $this->data['waitSeconds'];
	}

	public function setWaitSeconds($seconds)
	{
		$this->data['waitSeconds'] = $seconds;
	}

	public function setAttribute($key, $value)
	{
		$this->data[$key] = $value;
	}

	public function getAttribute($key)
	{
		return $this->data[$key];
	}

	public function getData()
	{
		return $this->data;
	}

	public function getLogData()
	{
		$logData = $this->data;
		unset($logData['token']);
		return $logData;
	}

	public function validate()
	{
		$allowedStatuses = array(
			self::STATUS_WAITING,
			self::STATUS_PROCESSING,
			self::STATUS_SUCCESS,
			self::STATUS_ERROR,
			self::STATUS_CANCELLED
		);

		if (!in_array($this->getStatus(), $allowedStatuses)) {
			throw new ApplicationException(
				"Job status has unrecongized value '"
				. $this->getStatus() . "'. Job status must be one of ("
				. implode(',',$allowedStatuses) . ")"
			);
		}
	}
}
