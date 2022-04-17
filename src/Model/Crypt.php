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
     * @var Bcrypt|null
     */
    private Bcrypt $bcrypt;
    
    public function __construct(array $config)
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
        
        $this->bcrypyt = new Bcrypt();
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

}
