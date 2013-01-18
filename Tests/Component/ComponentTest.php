<?php
/**
 * ComponentTest
 *
 * @author: Miroslav Čillík <miro@keboola.com>
 * @created: 7.1.13
 */

namespace Syrup\ComponentBundle\Tests\Component;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase,
	Symfony\Component\HttpFoundation\Response,
	Keboola\StorageApi\Client,
	Keboola\StorageApi\Table,
	Syrup\ComponentBundle\Component\DummyExtractor
;

class ComponentTest extends WebTestCase
{
	public function testDummyExtractor()
	{
		$client = static::createClient();
		$log = static::$kernel->getContainer()->get('logger');
		$sapiToken = static::$kernel->getContainer()->getParameter('storageApi.test.token');
		$storageApi = new Client($sapiToken);

		$extractor = new DummyExtractor($storageApi, $log);
		$extractor->run();

		$data = array(
			array('id', 'col1', 'col2', 'col3'),
			array('1', 'a', 'b', 'c'),
			array('2', 'd', 'e', 'f'),
			array('3', 'g', 'h', 'i'),
			array('4', 'j', 'k', 'l'),
		);

		$csv = $storageApi->exportTable('in.c-main.test');
		$table = new Table($storageApi, 'in.c-main.test');
		$testData = $table->csvStringToArray($csv);

		$this->assertEquals($data, $testData);
	}

}
