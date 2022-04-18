<?php

namespace FwsDoctrineAuth\Model;

use FwsDoctrineAuth\Model\Crypt;
use FwsDoctrineAuth\Exception\DoctrineAuthException;
use FwsDoctrineAuth\Entity\BaseUsers;

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
     * @param BaseUsers $identity
     * @param string $password
     * @return boolean
     * @throws DoctrineAuthException
     */
    static public function verifyCredential(BaseUsers $identity, string $password)
    {
        /* get credential getter if exists */
        $credential = self::$config['doctrine']['authentication']['orm_default']['credential_property']; // get credential
        $getter = 'get' . ucfirst($credential);
        if (!method_exists($identity, $getter)) {
            throw new DoctrineAuthException(sprintf('No getter "%s" found in %s', $getter, get_class($identity)));
        }
        
        /* Using raw password, registration login */
        if ($password === $identity->$getter()) {
            return true;
        }

        /* Check encrypted password */
        $crypt = new Crypt();
        return $crypt->bcryptVerify($password, $identity->$getter());
    }

}
