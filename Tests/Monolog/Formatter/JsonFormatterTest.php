<?php
/**
 * @package syrup-component-bundle
 * @copyright 2015 Keboola
 * @author Jakub Matejka <jakub@keboola.com>
 */

namespace SyrupComponentBundle\Tests\Monolog\Formatter;

use Monolog\Logger;
use Syrup\ComponentBundle\Monolog\Formatter\JsonFormatter;
use Syrup\ComponentBundle\Tests\Monolog\TestCase;

class JsonFormatterTest extends TestCase
{

    /**
     * @covers Syrup\ComponentBundle\Monolog\Formatter\JsonFormatter::format
     */
    public function testFormat()
    {
        $formatter = new JsonFormatter();
        $record = $this->getRecord();
        $expectedRecord = $record;
        unset($expectedRecord['level_name']);
        unset($expectedRecord['datetime']);
        $this->assertEquals(json_encode($expectedRecord)."\n", $formatter->format($record));

        $formatter = new JsonFormatter(JsonFormatter::BATCH_MODE_JSON, false);
        $this->assertEquals(json_encode($expectedRecord), $formatter->format($record));
    }

    /**
     * @covers Syrup\ComponentBundle\Monolog\Formatter\JsonFormatter::formatBatch
     * @covers Syrup\ComponentBundle\Monolog\Formatter\JsonFormatter::formatBatchJson
     */
    public function testFormatBatch()
    {
        $formatter = new JsonFormatter();
        $records = array(
            $this->getRecord(Logger::WARNING),
            $this->getRecord(Logger::DEBUG),
        );
        $expectedRecords = array();
        foreach ($records as $record) {
            unset($record['level_name']);
            unset($record['datetime']);
            $expectedRecords[] = $record;
        }
        $this->assertEquals(json_encode($expectedRecords), $formatter->formatBatch($records));
    }
}
