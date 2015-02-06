<?php
/**
 * Created by Miroslav Čillík <miro@keboola.com>
 * Date: 09/12/13
 * Time: 11:56
 */

namespace Syrup\ComponentBundle\Exception;

class NoRequestException extends SyrupComponentException
{

    public function __construct()
    {
        parent::__construct(500, 'Request not set');
    }
}
