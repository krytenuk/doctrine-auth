<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\PasswordReset;

use DateTimeImmutable;

interface PasswordResetStoreInterface
{
    /**
     * Save a new token, which replaces any earlier tokens the user has.
     */
    public function save(
        string $tokenHash,
        string $userId,
        string $ipAddress,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $expiresAt,
    ): void;

    /**
     * The user a token belongs to, if the token is unused and has not expired.
     */
    public function findUserId(string $tokenHash, DateTimeImmutable $now): ?string;

    /**
     * Use up a token. Only one caller can succeed, however many are racing to use it.
     */
    public function consume(string $tokenHash, DateTimeImmutable $now): bool;

    /**
     * Stop all of a user's tokens from working.
     */
    public function invalidateForUser(string $userId, DateTimeImmutable $now): void;

    public function countForUser(string $userId, DateTimeImmutable $since): int;

    public function countForIpAddress(string $ipAddress, DateTimeImmutable $since): int;

    public function purge(DateTimeImmutable $before): void;
}
