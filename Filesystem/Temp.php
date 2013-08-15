<?php
/**
 * Temp.php
 *
 * @author: Miroslav Čillík <miro@keboola.com>
 * @created: 15.8.13
 */

namespace Syrup\ComponentBundle\Filesystem;


use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Syrup\ComponentBundle\Component\Component;

class Temp
{
	/**
	 * @var Filesystem
	 */
	private $filesystem;

	/**
	 * @var Component
	 */
	private $component;

	/**
	 * @var \SplFileInfo[]
	 */
	private $files = array();

	/**
	 * Get path to temp directory
	 *
	 * @return string
	 */
	private function getTmpPath()
	{
		return sys_get_temp_dir() . "/" . $this->component->getFullName();
	}

	/**
	 * Constructor
	 *
	 * Creates directory for filesystem according to Syrup Component
	 *
	 * @param Component $component
	 */
	function __construct(Component $component)
	{
		$this->filesystem = new Filesystem();
		$this->component = $component;

		if (!file_exists($this->getTmpPath()) && !is_dir($this->getTmpPath())) {
			$this->filesystem->mkdir($this->getTmpPath(), 0770);
		}
	}

	/**
	 * Create empty file in TMP directory
	 *
	 * @param string $suffix filename suffix
	 * @return \SplFileInfo
	 * @throws \Exception|\Symfony\Component\Filesystem\Exception\IOException
	 */
	public function createTmpFile($suffix = null)
	{
		$file = uniqid();

		if ($suffix) {
			$file .= '-' . $suffix;
		}

		$fileInfo = new \SplFileInfo($this->getTmpPath() . '/' . $file);

		try {
			$this->filesystem->touch($fileInfo);
			$this->files[] = $fileInfo;
			$this->filesystem->chmod($fileInfo, 0600);

			return $fileInfo;
		} catch (IOException $e) {
			throw $e;
		}
	}

	/**
	 * Destructor
	 *
	 * Delete all files created by syrup component run
	 */
	function __destruct()
	{
		foreach ($this->files AS $fileInfo) {
			if (file_exists($fileInfo) && is_file($fileInfo)) {
				unlink($fileInfo);
			}
		}
	}
}
