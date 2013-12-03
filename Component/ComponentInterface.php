<?php

/*
 * Created by JetBrains PhpStorm.
 * User: Miro
 * Date: 23.11.12
 * Time: 16:31
 */

namespace Syrup\ComponentBundle\Component;

/**
 * All interfaces should implement ComponentInterface
 */
use Symfony\Component\DependencyInjection\ContainerInterface;

interface ComponentInterface
{
	public function postRun($params);

	/**
	 * @param ContainerInterface $container
	 * @return mixed
	 */
	public function setContainer($container);
}
