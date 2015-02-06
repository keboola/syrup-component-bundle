<?php
/**
 * @package syrup-component-bundle
 * @copyright 2015 Keboola
 * @author Jakub Matejka <jakub@keboola.com>
 */

namespace Syrup\ComponentBundle\Tests\Monolog;

use Monolog\Logger;

class TestCase extends \PHPUnit_Framework_TestCase
{

    protected function getRecord($level = Logger::WARNING, $message = 'test', $context = array())
    {
        return [
            'message' => $message,
            'context' => $context,
            'level' => $level,
            'level_name' => Logger::getLevelName($level),
            'channel' => 'test',
            'datetime' => \DateTime::createFromFormat('U.u', sprintf('%.6F', microtime(true))),
            'extra' => array(),
        ];
    }
}
