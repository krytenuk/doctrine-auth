<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\Password;

use function password_hash;
use function password_needs_rehash;
use function password_verify;

use const PASSWORD_DEFAULT;

/**
 * Uses PHP's password functions. Hashes made by laminas-crypt's Bcrypt verify without any conversion.
 */
final class NativePasswordHasher implements PasswordHasherInterface
{
    /**
     * @param array<string, int> $options Options for password_hash()
     */
    public function __construct(
        private readonly string $algorithm = PASSWORD_DEFAULT,
        private readonly array $options = [],
    ) {
    }

    public function hash(string $password): string
    {
        return password_hash($password, $this->algorithm, $this->options);
    }

    public function verify(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    public function needsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, $this->algorithm, $this->options);
    }
}
