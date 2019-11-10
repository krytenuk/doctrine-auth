<?php

namespace FwsDoctrineAuth\Model;

use Zend\Crypt\Password\Bcrypt;
use FwsDoctrineAuth\Exception\DoctrineAuthException;

class HashPassword
{

    /**
     *
     * @var array
     */
    private static $config;

    static public function setConfig(Array $config)
    {
        self::$config = $config;
    }

    /**
     * Check credentials (passwords) match
     * @param string $identity
     * @param string $password
     * @return boolean
     * @throws DoctrineAuthException
     */
    static public function verifyCredential($identity, $password)
    {
        $credential = self::$config['doctrine']['authentication']['orm_default']['credential_property']; // get credential

        $getter = 'get' . ucfirst($credential);
        if (!method_exists($identity, $getter)) {
            throw new DoctrineAuthException(sprintf('No getter "%s" found in %s', $getter, get_class($identity)));
        }


        $bcrypt = new Bcrypt();
        return $bcrypt->verify($password, $identity->$getter());
    }

}
