<?php

declare(strict_types=1);

namespace FwsDoctrineAuthTest\Support;

use DateTimeImmutable;
use FwsDoctrineAuth\PasswordReset\PasswordResetStoreInterface;

final class InMemoryPasswordResetStore implements PasswordResetStoreInterface
{
    /** @var array<string, array{userId: string, ip: string, created: DateTimeImmutable, expires: DateTimeImmutable, consumed: bool}> */
    public array $rows = [];

    /** Behave as if another request used the token first */
    public bool $loseConsumeRace = false;

    public function save(string $tokenHash, string $userId, string $ipAddress, DateTimeImmutable $createdAt, DateTimeImmutable $expiresAt): void
    {
        $this->invalidateForUser($userId, $createdAt);
        $this->rows[$tokenHash] = ['userId' => $userId, 'ip' => $ipAddress, 'created' => $createdAt, 'expires' => $expiresAt, 'consumed' => false];
    }

    public function findUserId(string $tokenHash, DateTimeImmutable $now): ?string
    {
        $row = $this->rows[$tokenHash] ?? null;

        return $row !== null && ! $row['consumed'] && $row['expires'] > $now ? $row['userId'] : null;
    }

    public function consume(string $tokenHash, DateTimeImmutable $now): bool
    {
        if ($this->loseConsumeRace) {
            return false;
        }

        if ($this->findUserId($tokenHash, $now) === null) {
            return false;
        }
        $this->rows[$tokenHash]['consumed'] = true;

        return true;
    }

    public function invalidateForUser(string $userId, DateTimeImmutable $now): void
    {
        foreach ($this->rows as $hash => $row) {
            if ($row['userId'] === $userId) {
                $this->rows[$hash]['consumed'] = true;
            }
        }
    }

    public function countForUser(string $userId, DateTimeImmutable $since): int
    {
        return count(array_filter($this->rows, static fn (array $row): bool => $row['userId'] === $userId && $row['created'] > $since));
    }

    public function countForIpAddress(string $ipAddress, DateTimeImmutable $since): int
    {
        return count(array_filter($this->rows, static fn (array $row): bool => $row['ip'] === $ipAddress && $row['created'] > $since));
    }

    public function purge(DateTimeImmutable $before): void
    {
        $this->rows = array_filter($this->rows, static fn (array $row): bool => $row['created'] > $before);
    }
}
