<?php
/**
 * Created by Miroslav Čillík <miro@keboola.com>
 * Date: 02/12/13
 * Time: 16:38
 */

namespace Syrup\ComponentBundle\Service\SharedSapi;


abstract class Event
{
	protected $id;

	protected $table;

	protected $header;

	public function getTable()
	{
		return $this->table;
	}

	public function getHeader()
	{
		return $this->header;
	}

	public function getId()
	{
		return $this->id;
	}

	public function setId($id)
	{
		$this->id = $id;
	}

	public abstract function toArray();
} 