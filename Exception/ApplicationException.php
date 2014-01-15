<?php
/**
 * Created by Miroslav Čillík <miro@keboola.com>
 * Date: 15/01/14
 * Time: 13:11
 */

namespace Syrup\ComponentBundle\Exception;


class ApplicationException extends SyrupComponentException
{
	public function __construct($message)
	{
		parent::__construct(500, 'Application error: ' . $message);
	}
} 