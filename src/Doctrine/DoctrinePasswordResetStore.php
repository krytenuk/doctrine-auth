<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\Doctrine;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use FwsDoctrineAuth\Entity\PasswordReminder;
use FwsDoctrineAuth\PasswordReset\PasswordResetStoreInterface;

final class DoctrinePasswordResetStore implements PasswordResetStoreInterface
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function save(
        string $tokenHash,
        string $userId,
        string $ipAddress,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $expiresAt,
    ): void {
        $this->invalidateForUser($userId, $createdAt);
        $this->entityManager->persist(new PasswordReminder($tokenHash, $userId, $ipAddress, $createdAt, $expiresAt));
        $this->entityManager->flush();
    }

    public function findUserId(string $tokenHash, DateTimeImmutable $now): ?string
    {
        $userId = $this->entityManager->createQueryBuilder()
            ->select('r.userId')
            ->from(PasswordReminder::class, 'r')
            ->where('r.tokenHash = :hash AND r.consumedAt IS NULL AND r.expiresAt > :now')
            ->setParameter('hash', $tokenHash)
            ->setParameter('now', $now, Types::DATETIME_IMMUTABLE)
            ->getQuery()
            ->getOneOrNullResult();

        return $userId['userId'] ?? null;
    }

    public function consume(string $tokenHash, DateTimeImmutable $now): bool
    {
        $updated = $this->entityManager->createQueryBuilder()
            ->update(PasswordReminder::class, 'r')
            ->set('r.consumedAt', ':now')
            ->where('r.tokenHash = :hash AND r.consumedAt IS NULL AND r.expiresAt > :now')
            ->setParameter('hash', $tokenHash)
            ->setParameter('now', $now, Types::DATETIME_IMMUTABLE)
            ->getQuery()
            ->execute();

        return $updated === 1;
    }

    public function invalidateForUser(string $userId, DateTimeImmutable $now): void
    {
        $this->entityManager->createQueryBuilder()
            ->update(PasswordReminder::class, 'r')
            ->set('r.consumedAt', ':now')
            ->where('r.userId = :user AND r.consumedAt IS NULL')
            ->setParameter('user', $userId)
            ->setParameter('now', $now, Types::DATETIME_IMMUTABLE)
            ->getQuery()
            ->execute();
    }

    public function countForUser(string $userId, DateTimeImmutable $since): int
    {
        return $this->count('r.userId', $userId, $since);
    }

    public function countForIpAddress(string $ipAddress, DateTimeImmutable $since): int
    {
        return $this->count('r.ipAddress', $ipAddress, $since);
    }

    private function count(string $field, string $value, DateTimeImmutable $since): int
    {
        return (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(r)')
            ->from(PasswordReminder::class, 'r')
            ->where($field . ' = :value AND r.createdAt > :since')
            ->setParameter('value', $value)
            ->setParameter('since', $since, Types::DATETIME_IMMUTABLE)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function purge(DateTimeImmutable $before): void
    {
        $this->entityManager->createQueryBuilder()
            ->delete(PasswordReminder::class, 'r')
            ->where('r.createdAt <= :before')
            ->setParameter('before', $before, Types::DATETIME_IMMUTABLE)
            ->getQuery()
            ->execute();
    }
}
