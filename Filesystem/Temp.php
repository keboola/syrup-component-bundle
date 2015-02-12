<?php
/**
 * User: Ondrej Vana
 */

namespace Syrup\ComponentBundle\Filesystem;

use Keboola\Temp\Temp as KeboolaTemp;

/**
 * @deprecated use Keboola\Temp\Temp instead (https://github.com/keboola/php-temp)
 * @brief A blank Temp class to provide backwards compatibility after separating a Temp class
 */
class Temp extends KeboolaTemp
{

}
