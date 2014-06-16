<?php
/**
 * Created by Miroslav Čillík <miro@keboola.com>
 * Date: 21/01/14
 * Time: 11:47
 */

namespace Syrup\ComponentBundle\Encryption;


use Keboola\Encryption\AesEncryptor;
use Keboola\Encryption\EncryptorInterface;

class Encryptor implements EncryptorInterface
{
	/** @var AesEncryptor */
	protected $encryptor;

	public function __construct($key)
	{
		$this->encryptor = new AesEncryptor($key);
	}

	/**
	 * @param $data string data to encrypt
	 * @return string encrypted data
	 */
	public function encrypt($data)
	{
		return base64_encode($this->encryptor->encrypt($data));
	}

	/**
	 * @param $encryptedData string
	 * @return string decrypted data
	 */
	public function decrypt($encryptedData)
	{
		return $this->encryptor->decrypt(base64_decode($encryptedData));
	}
}
