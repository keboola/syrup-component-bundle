<?php
/**
 * Created by Miroslav Čillík <miro@keboola.com>
 * Date: 28/05/14
 * Time: 14:37
 */

namespace Syrup\ComponentBundle\Job;


use Syrup\ComponentBundle\Exception\ApplicationException;

class Job implements JobInterface
{
	const STATUS_NEW = 'new';
	const STATUS_PROCESSING = 'processing';
	const STATUS_SUCCESS = 'success';
	const STATUS_ERROR = 'error';

	protected $data;

	private $requiredFields = [
		'id', 'projectId', 'token', 'component', 'command', 'status', 'result'
	];

	protected $optionalFields = [];

	public function __construct($data = [])
	{
		$this->data = $data;
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
		$this->data['commnad'] = $cmd;
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

	public function getData()
	{
		$fields = array_merge($this->requiredFields, $this->optionalFields);

		return array_intersect_key($this->data, array_flip($fields));
	}

	public function validate()
	{
		$fields = array_merge($this->requiredFields, $this->optionalFields);

		foreach ($fields as $field) {
			if (!array_key_exists($field, $this->data)) {
				throw new ApplicationException("Job is missing required field '".$field."'.");
			}
		}

		$this->validateStatus();
	}

	protected function validateStatus()
	{
		$allowedStatuses = array(self::STATUS_NEW, self::STATUS_PROCESSING, self::STATUS_SUCCESS, self::STATUS_ERROR);

		if (!in_array($this->getStatus(), $allowedStatuses)) {
			throw new ApplicationException("Job status has unrecongized value '".$this->getStatus()."'. Job status must be one of (".implode(',',$allowedStatuses).")");
		}
	}
}
