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

	public function getProjectId();

	public function setProjectId($id);

	public function getToken();

	public function setToken($token);

	public function getCommand();

	public function setCommand($cmd);

	public function getStatus();

	public function setStatus($status);

	public function setResult($result);

	public function getResult();

	public function setRunId($runId);

	public function getRunId();

	public function setLockName($lockName);

	public function getLockName();

}
