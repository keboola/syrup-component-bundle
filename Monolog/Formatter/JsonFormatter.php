<?php
/**
 * @package syrup-component-bundle
 * @copyright 2015 Keboola
 * @author Jakub Matejka <jakub@keboola.com>
 */

namespace Syrup\ComponentBundle\Monolog\Formatter;

class JsonFormatter extends \Monolog\Formatter\JsonFormatter
{

    public function format(array $record)
    {
        unset($record['level_name']);
        unset($record['datetime']);
        return json_encode($record) . ($this->appendNewline ? "\n" : '');
    }

    /**
     * Return a JSON-encoded array of records.
     *
     * @param  array  $records
     * @return string
     */
    protected function formatBatchJson(array $records)
    {
        foreach ($records as &$record) {
            unset($record['level_name']);
            unset($record['datetime']);
        }
        return json_encode($records);
    }
}
