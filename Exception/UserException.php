<?php
/**
 * Created by Miroslav Čillík <miro@keboola.com>
 * Date: 19/12/13
 * Time: 12:22
 */

namespace Syrup\ComponentBundle\Exception;

class UserException extends SyrupComponentException
{
    public function __construct($message, $previous = null, $data = [])
    {
        parent::__construct(400, 'User error: ' . $message, $previous, $data);
    }
}
