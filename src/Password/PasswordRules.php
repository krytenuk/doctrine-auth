<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\Password;

use function str_contains;
use function strlen;

/**
 * The limits the module itself needs. Your own password policy, such as a minimum length, belongs in your application.
 */
final class PasswordRules
{
    private const MAX_LENGTH = 4096;

    /**
     * Very long passwords would make hashing slow enough to use as an attack, and bcrypt refuses null bytes.
     */
    public static function isAcceptable(string $password): bool
    {
        return $password !== ''
            && strlen($password) <= self::MAX_LENGTH
            && ! str_contains($password, "\0");
    }
}
