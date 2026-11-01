<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\Doctrine;

use DateInterval;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use FwsDoctrineAuth\Entity\FailedLoginAttemptsLog;
use FwsDoctrineAuth\Entity\IpBlocked;
use FwsDoctrineAuth\Exception\DoctrineAuthException;
use FwsDoctrineAuth\Throttle\LoginThrottleInterface;
use FwsDoctrineAuth\Throttle\ThrottleStatus;
use Psr\Clock\ClockInterface;

use function array_key_last;
use function count;
use function hash_hmac;
use function max;
use function mb_strtolower;
use function trim;

/**
 * Limits failed logins per account and per IP address.
 *
 * The account limit stops attacks that rotate IP addresses. It is temporary and recovers by itself, so nobody can lock
 * a user out for good. Too many failures from one address blocks that address, stopping one client trying many accounts.
 */
final class DoctrineLoginThrottle implements LoginThrottleInterface
{
    /**
     * @param array{
     *     account: array{max_attempts: int, window: int},
     *     ip: array{max_attempts: int, window: int, block_for: int}
     * } $limits Times are in seconds. A max_attempts of 0 turns that limit off, a block_for of 0 blocks with no end
     * @param string $secret Key for hashing identities, so stored hashes cannot be matched to known email addresses
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ClockInterface $clock,
        private readonly array $limits,
        private readonly string $secret,
    ) {
        if ($secret === '') {
            throw new DoctrineAuthException('doctrine-auth.throttle.secret is not set');
        }
    }

    public function check(string $identity, string $ipAddress): ThrottleStatus
    {
        $now = $this->clock->now();

        $block = $this->entityManager->createQueryBuilder()
            ->select('b')
            ->from(IpBlocked::class, 'b')
            ->where('b.ipAddress = :ip AND (b.expiresAt IS NULL OR b.expiresAt > :now)')
            ->setParameter('ip', $ipAddress)
            ->setParameter('now', $now, Types::DATETIME_IMMUTABLE)
            ->orderBy('b.expiresAt', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($block instanceof IpBlocked) {
            $expiresAt = $block->getExpiresAt();

            return ThrottleStatus::blocked($expiresAt === null ? null : $this->secondsBetween($now, $expiresAt));
        }

        return $this->checkAccount($identity, $now);
    }

    private function checkAccount(string $identity, DateTimeImmutable $now): ThrottleStatus
    {
        $maximum = $this->limits['account']['max_attempts'];
        if ($maximum < 1) {
            return ThrottleStatus::allowed();
        }

        $window   = $this->limits['account']['window'];
        $attempts = $this->entityManager->createQueryBuilder()
            ->select('a')
            ->from(FailedLoginAttemptsLog::class, 'a')
            ->where('a.identityHash = :hash AND a.attemptedAt > :since')
            ->setParameter('hash', $this->hash($identity))
            ->setParameter('since', $this->ago($now, $window), Types::DATETIME_IMMUTABLE)
            ->orderBy('a.attemptedAt', 'DESC')
            ->setMaxResults($maximum)
            ->getQuery()
            ->getResult();

        if (count($attempts) < $maximum) {
            return ThrottleStatus::allowed();
        }

        /** Allowed again once the oldest of these attempts has left the window */
        $oldest = $attempts[array_key_last($attempts)]->getAttemptedAt();

        return ThrottleStatus::blocked(
            $this->secondsBetween($now, $oldest->add(new DateInterval('PT' . $window . 'S')))
        );
    }

    public function recordFailure(string $identity, string $ipAddress): void
    {
        $now = $this->clock->now();

        $this->entityManager->persist(new FailedLoginAttemptsLog($this->hash($identity), $ipAddress, $now));
        $this->entityManager->flush();

        $this->blockIpAddressIfNeeded($ipAddress, $now);
        $this->deleteOldAttempts($now);
    }

    private function blockIpAddressIfNeeded(string $ipAddress, DateTimeImmutable $now): void
    {
        $maximum = $this->limits['ip']['max_attempts'];
        if ($maximum < 1) {
            return;
        }

        $failures = (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(a)')
            ->from(FailedLoginAttemptsLog::class, 'a')
            ->where('a.ipAddress = :ip AND a.attemptedAt > :since')
            ->setParameter('ip', $ipAddress)
            ->setParameter('since', $this->ago($now, $this->limits['ip']['window']), Types::DATETIME_IMMUTABLE)
            ->getQuery()
            ->getSingleScalarResult();

        if ($failures < $maximum) {
            return;
        }

        $blockFor = $this->limits['ip']['block_for'];
        $this->entityManager->persist(new IpBlocked(
            $ipAddress,
            $now,
            $blockFor > 0 ? $now->add(new DateInterval('PT' . $blockFor . 'S')) : null
        ));
        $this->entityManager->flush();
    }

    /**
     * Attempts older than both windows no longer count towards anything, and neither do expired blocks.
     */
    private function deleteOldAttempts(DateTimeImmutable $now): void
    {
        $this->entityManager->createQueryBuilder()
            ->delete(FailedLoginAttemptsLog::class, 'a')
            ->where('a.attemptedAt <= :before')
            ->setParameter(
                'before',
                $this->ago($now, max($this->limits['account']['window'], $this->limits['ip']['window'])),
                Types::DATETIME_IMMUTABLE
            )
            ->getQuery()
            ->execute();

        $this->entityManager->createQueryBuilder()
            ->delete(IpBlocked::class, 'b')
            ->where('b.expiresAt <= :now')
            ->setParameter('now', $now, Types::DATETIME_IMMUTABLE)
            ->getQuery()
            ->execute();
    }

    public function clear(string $identity): void
    {
        $this->entityManager->createQueryBuilder()
            ->delete(FailedLoginAttemptsLog::class, 'a')
            ->where('a.identityHash = :hash')
            ->setParameter('hash', $this->hash($identity))
            ->getQuery()
            ->execute();
    }

    private function hash(string $identity): string
    {
        return hash_hmac('sha256', mb_strtolower(trim($identity)), $this->secret);
    }

    private function ago(DateTimeImmutable $now, int $seconds): DateTimeImmutable
    {
        return $now->sub(new DateInterval('PT' . $seconds . 'S'));
    }

    private function secondsBetween(DateTimeImmutable $from, DateTimeImmutable $to): int
    {
        return max(1, $to->getTimestamp() - $from->getTimestamp());
    }
}
