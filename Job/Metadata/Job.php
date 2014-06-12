<?php
/**
 * Created by Miroslav Čillík <miro@keboola.com>
 * Date: 28/05/14
 * Time: 14:37
 */

namespace Syrup\ComponentBundle\Job\Metadata;


use Syrup\ComponentBundle\Exception\ApplicationException;

class Job implements JobInterface
{
	const STATUS_WAITING = 'waiting';
	const STATUS_PROCESSING = 'processing';
	const STATUS_SUCCESS = 'success';
	const STATUS_ERROR = 'error';

	protected $data;

	private $requiredFields = [
		'id', 'runId', 'lockName', 'projectId', 'token', 'component', 'command', 'status', 'result'
	];

	public function __construct($data = [])
	{
		$this->data = array_combine($this->requiredFields, array_fill(0, count($this->requiredFields), null));
		$this->data = array_merge($this->data, $data);

		if (!isset($data['lockName'])) {
			$this->setLockName($this->getComponent() . '-' . $this->getProjectId());
		}
		$this->data['status'] = self::STATUS_WAITING;
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

	public function getProjectId()
	{
		return (int) $this->data['projectId'];
	}

	public function setProjectId($id)
	{
		$this->data['projectId'] = (int) $id;
		return $this;
	}

	public function getToken()
	{
		return $this->data['token'];
	}

	public function setToken($token)
	{
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

	public function validate()
	{
		foreach ($this->requiredFields as $field) {
			if (!array_key_exists($field, $this->data) && !is_null($this->data[$field])) {
				throw new ApplicationException("Job is missing required field '".$field."'.");
			}
		}

		$this->validateStatus();
	}

	protected function validateStatus()
	{
		$allowedStatuses = array(self::STATUS_WAITING, self::STATUS_PROCESSING, self::STATUS_SUCCESS, self::STATUS_ERROR);

		if (!in_array($this->getStatus(), $allowedStatuses)) {
			throw new ApplicationException("Job status has unrecongized value '".$this->getStatus()."'. Job status must be one of (".implode(',',$allowedStatuses).")");
		}
	}
}
