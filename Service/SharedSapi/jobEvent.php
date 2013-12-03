<?php
/**
 * Created by Miroslav Čillík <miro@keboola.com>
 * Date: 02/12/13
 * Time: 16:10
 */

namespace Syrup\ComponentBundle\Service\SharedSapi;


class jobEvent extends Event
{
	protected $table = 'jobs';

	protected $id;

	protected $component;

	protected $action;

	protected $url;

	protected $token;

	protected $tokenId;

	protected $tokenDesc;

	protected $tokenOwnerName;

	protected $status;

	protected $startTime;

	protected $endTime;

	protected $request;

	protected $header = array(
		'id', 'component', 'action', 'url', 'token', 'tokenId',
		'tokenDesc', 'tokenOwnerName', 'status', 'startTime', 'endTime', 'request'
	);

	public function __construct($data = array())
	{
		foreach ($data as $k => $v) {
			if (property_exists($this, $k)) {
				$this->$k = $v;
			}
		}
	}

	public function getHeader()
	{
		return $this->header;
	}

	public function toArray()
	{
		$arr = array();
		foreach ($this->header as $key) {
			$arr[$key] = $this->$key;
		}

		return $arr;
	}

	public function setId($id)
	{
		$this->id = $id;
		return $this;
	}

	public function setComponent($component)
	{
		$this->component = $component;
	}

	public function setAction($action)
	{
		$this->action = $action;
	}

	public function setUrl($url)
	{
		$this->url = $url;
	}

	public function setToken($token)
	{
		$this->token = $token;
	}

	public function setTokenId($tokenId)
	{
		$this->tokenId = $tokenId;
	}

	public function setTokenDesc($tokenDesc)
	{
		$this->tokenDesc = $tokenDesc;
	}

	public function setTokenOwner($tokenOwner)
	{
		$this->tokenOwnerName = $tokenOwner;
	}

	public function setStatus($status)
	{
		$this->status = $status;
	}

	public function setStartTime($time)
	{
		$this->startTime = $time;
	}

	public function setEndTime($time)
	{
		$this->endTime = $time;
	}

	public function setRequest($params)
	{
		$this->request = $params;
	}

} 