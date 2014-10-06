<?php
/**
 * Created by Miroslav Čillík <miro@keboola.com>
 * Date: 28/05/14
 * Time: 17:19
 */

namespace Syrup\ComponentBundle\Job\Metadata;


interface JobInterface
{
	public function validate();

	public function getData();

	public function getComponent();

	public function setComponent($component);

	public function getId();

	public function setId($id);

	public function getProject();

	public function setProject(array $project);

	public function getToken();

	public function setToken(array $token);

	public function getCommand();

	public function setCommand($cmd);

	public function getParams();

	public function setParams(array $params);

	public function getStatus();

	public function setStatus($status);

	public function setResult($result);

	public function getResult();

	public function setRunId($runId);

	public function getRunId();

	public function setLockName($lockName);

	public function getLockName();

	public function getProcess();

	public function setProcess(array $process);

	public function setCreatedTime($datetime);

	public function getCreatedTime();

	public function getStartTime();

	public function setStartTime($time);

	public function getEndTime();

	public function setEndTime($time);

	public function setDurationSeconds($seconds);

	public function getDurationSeconds();

	public function setWaitSeconds($seconds);

	public function getWaitSeconds();

	public function getIndex();

	public function getType();
}
