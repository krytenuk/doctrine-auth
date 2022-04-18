<?php

namespace FwsDoctrineAuth\Model;

use Laminas\Crypt\PublicKey\Rsa;
use Laminas\Crypt\Password\Bcrypt;
use FwsDoctrineAuth\Exception\DoctrineAuthException;

/**
 * Description of Crypt
 *
 * @author Garry Childs <info@freedomwebservices.net>
 */
class Crypt
{

    /**
     * 
     * @var array
     */
    private array $config;

    /**
     * 
     * @var Rsa
     */
    private Rsa $rsa;

    /**
     * 
     * @var Bcrypt
     */
    private Bcrypt $bcrypt;

    public function __construct(?array $config = null)
    {
        $this->bcrypt = new Bcrypt();

        if ($config === null) {
            return $this;
        }

        if ($config instanceof Traversable) {
            $config = ArrayUtils::iteratorToArray($config);
        }

        $this->setConfig($config);
    }

    /**
     * Set config and setup RSA
     * @param array $config
     * @return Crypt
     * @throws DoctrineAuthException
     */
    public function setConfig(array $config): Crypt
    {
        if (!isset($config['doctrineAuth']['rsaPublicKeyFile'])) {
            throw new DoctrineAuthException('rsaPublicKeyFile key not set in config');
        }
        if (!isset($config['doctrineAuth']['rsaPrivateKeyFile'])) {
            throw new DoctrineAuthException('rsaPrivateKeyFile key not set in config');
        }
        if (!isset($config['doctrineAuth']['rsaKeyPassphrase'])) {
            throw new DoctrineAuthException('rsaKeyPassphrase key not set in config');
        }

        $this->rsa = Rsa::factory([
                    'public_key' => $config['doctrineAuth']['rsaPublicKeyFile'],
                    'private_key' => $config['doctrineAuth']['rsaPrivateKeyFile'],
                    'pass_phrase' => $config['doctrineAuth']['rsaKeyPassphrase'],
                    'binary_output' => false,
        ]);
        return $this;
    }

    /**
     * Encrypt value
     * @param string $value
     * @return string
     */
    public function rsaEncrypt(string $value): string
    {
        return $this->rsa->encrypt($value);
    }

    /**
     * Decrypt value
     * @param string $value
     * @return string
     */
    public function rsaDecrypt(string $value): string
    {
        return $this->rsa->decrypt($value);
    }

    /**
     * Encrypt password
     * @param type $password
     * @return type
     */
    public function bcrypytCreate($password)
    {
        return $this->bcrypt->create($password);
    }

    /**
     * Check password
     * @param string $password
     * @param string $hash
     * @return bool
     */
    public function bcryptVerify(string $password, string $hash): bool
    {
        return $this->bcrypt->verify($password, $hash);
    }
  
}
