<?php

declare(strict_types=1);

namespace FwsDoctrineAuthTest\Support;

use FwsDoctrineAuth\Password\NativePasswordHasher;
use FwsDoctrineAuth\Password\PasswordHasherInterface;

final class CountingHasher implements PasswordHasherInterface
{
    public int $hashes = 0;

    public int $verifies = 0;

    public function __construct(private readonly PasswordHasherInterface $hasher = new NativePasswordHasher(PASSWORD_BCRYPT, ['cost' => 4]))
    {
    }

    public function hash(string $password): string
    {
        $this->hashes++;

        return $this->hasher->hash($password);
    }

    public function verify(string $password, string $hash): bool
    {
        $this->verifies++;

        return $this->hasher->verify($password, $hash);
    }

    public function needsRehash(string $hash): bool
    {
        return $this->hasher->needsRehash($hash);
    }
}
