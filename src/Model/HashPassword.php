<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\Model;

use FwsDoctrineAuth\Entity\BaseUser;
use FwsDoctrineAuth\Exception\DoctrineAuthException;
use Laminas\Crypt\Password\Bcrypt;

use function method_exists;
use function sprintf;
use function ucfirst;

class HashPassword
{
    private static array $config;

    public static function setConfig(array $config): void
    {
        self::$config = $config;
    }

    /**
     * Check credentials (passwords) match
     *
     * @throws DoctrineAuthException
     */
    public static function verifyCredential(BaseUser $identity, string $password): bool
    {
        /* get credential getter if exists */
        $credential = self::$config['doctrine']['authentication']['orm_default']['credential_property']; // get credential
        $getter     = 'get' . ucfirst($credential);
        if (! method_exists($identity, $getter)) {
            throw new DoctrineAuthException(sprintf('No getter "%s" found in %s', $getter, $identity::class));
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
