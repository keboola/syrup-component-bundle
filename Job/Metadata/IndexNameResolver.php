<?php
/**
 * Created by JetBrains PhpStorm.
 * User: martinhalamicek
 * Date: 14/11/14
 * Time: 09:22
 * To change this template use File | Settings | File Templates.
 */

namespace Syrup\ComponentBundle\Job\Metadata;


class IndexNameResolver {

	/**
	 * Returns newest index name
	 * Expected indices format: some_prefix_YYYY_VERSION where VERSION is number
	 *
	 * @param array $indexNames
	 * @return mixed
	 */
	public static function getLastIndexName(array $indexNames)
	{
		usort($indexNames, function($a, $b) {
			$aYear = self::getYearFromIndexName($a);
			$bYear = self::getYearFromIndexName($b);

			if ($aYear == $bYear) {
				$aVersion = self::getVersionFromIndexName($a);
				$bVersion = self::getVersionFromIndexName($b);

				if ($aVersion == $bVersion) {
					return 0;
				}
				return ($aVersion < $bVersion) ? -1 : 1;
			}

			return ($aYear < $bYear) ? -1 : 1;
		});
		return array_pop($indexNames);
	}

	public static function getVersionFromIndexName($indexName)
	{
		self::validateIndexNameFormat($indexName);
		$parts = explode('_', $indexName);
		return (int) array_pop($parts);
	}

	public static function getYearFromIndexName($indexName)
	{
		self::validateIndexNameFormat($indexName);
		$parts = explode('_', $indexName);
		return (int) $parts[count($parts) - 2];
	}

	public static function validateIndexNameFormat($indexName)
	{
		$parts = explode('_', $indexName);
		if (count($parts) < 3) {
			throw new \Exception("Invalid index name: $indexName");
		}
	}


}