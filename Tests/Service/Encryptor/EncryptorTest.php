<?php
/**
 * Created by Miroslav Čillík <miro@keboola.com>
 * Date: 19/02/14
 * Time: 17:27
 */

namespace Syrup\ComponentBundle\Tests\Service\Encryptor;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Syrup\ComponentBundle\Service\Encryption\Encryptor;
use Syrup\ComponentBundle\Service\Encryption\EncryptorFactory;

class EncryptorTest extends WebTestCase
{

    public function testEncryptor()
    {
        $client = static::createClient();
        $container = $client->getContainer();

        $encryptor = $container->get('syrup.encryptor');

        $encrypted = $encryptor->encrypt('secret');

        $this->assertEquals('secret', $encryptor->decrypt($encrypted));
    }
}
