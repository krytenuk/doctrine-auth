<?php

namespace FwsDoctrineAuth\Model;

use FwsDoctrineAuth\Exception\DoctrineAuthException;
use FwsDoctrineAuth\Entity\BaseUser;
use Laminas\Crypt\Password\Bcrypt;

class HashPassword
{

    private static array $config;

    static public function setConfig(array $config): void
    {
        self::$config = $config;
    }

    /**
     * Check credentials (passwords) match
     * @param BaseUser $identity
     * @param string $password
     * @return boolean
     * @throws DoctrineAuthException
     */
    static public function verifyCredential(BaseUser $identity, string $password): bool
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
        $bcrypt = new Bcrypt();
        return $bcrypt->verify($password, $identity->$getter());
    }

}
