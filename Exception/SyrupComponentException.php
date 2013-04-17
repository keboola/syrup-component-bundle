<?php
/**
 * SyrupComponentException.php
 *
 * @author: Miroslav Čillík <miro@keboola.com>
 * @created: 17.4.13
 */

namespace Syrup\ComponentBundle\Exception;


use Symfony\Component\HttpKernel\Exception\HttpException;

class SyrupComponentException extends HttpException implements SyrupExceptionInterface {

	protected $data = array();

	/**
	 * @return array
	 */
	public function getData()
	{
		return $this->data;
	}

	/**
	 * @param array $data
	 * @return $this
	 */
	public function setData(array $data)
	{
		$this->data = $data;

		return $this;
	}
}
