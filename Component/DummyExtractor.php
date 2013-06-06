<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Miro
 * Date: 27.11.12
 * Time: 11:44
 * To change this template use File | Settings | File Templates.
 */

namespace Syrup\ComponentBundle\Component;

use Symfony\Component\HttpKernel\Exception\HttpException;
use \Syrup\ComponentBundle\Component\Component;
use Keboola\StorageApi\Table;

class DummyExtractor extends Component
{
	protected $_name = 'dummy';
	protected $_prefix = 'ex';

	public function info($params)
	{
		return array(
			'info'  => array(
				'component' => $this->getFullName(),
				'documentation' => 'url to documentation'
			)
		);
	}

	protected function _process($config, $params)
	{
		// Get some data
		$data = array(
			array('id', 'col1', 'col2', 'col3'),
			array('1', 'a', 'b', 'c'),
			array('2', 'd', 'e', 'f'),
			array('3', 'g', 'h', 'i'),
			array('4', 'j', 'k', 'l'),
		);

		if (isset($params['error']) && $params['error'] == 1) {
			throw new \Exception("Oooops, something went wrong");
		}

		if (isset($params['userError']) && $params['userError'] == 1) {
			throw new HttpException(400, "User Exception occured");
		}

		if (isset($params['fatalError']) && $params['fatalError'] == 1) {

			$foo = new NonExistingClass();
			$foo->bar();
		}

		$outTable = 'in.c-test.dummy';
		if (isset($params['outputTable'])) {
			$outTable = $params['outputTable'];
		}

		$table = new Table($this->_storageApi, $outTable);

		$table->setFromArray($data, $hasHeader = true);

		$this->_results = array($table);

		return array(
			'table' => $table->getName()
		);
	}
}
