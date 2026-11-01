<?php

declare(strict_types=1);

namespace FwsDoctrineAuthTest\Doctrine;

use FwsDoctrineAuth\Doctrine\DoctrinePasswordResetStore;
use FwsDoctrineAuth\Entity\PasswordReminder;
use FwsDoctrineAuthTest\Support\DoctrineTestCase;
use FwsDoctrineAuthTest\Support\FakeClock;

final class DoctrinePasswordResetStoreTest extends DoctrineTestCase
{
    private DoctrinePasswordResetStore $store;
    private FakeClock $clock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->store = new DoctrinePasswordResetStore($this->entityManager);
        $this->clock = new FakeClock();
    }

    private function save(string $hash = 'hash', string $user = '7', string $ip = '203.0.113.5', int $lifetime = 600): void
    {
        $now = $this->clock->now();
        $this->store->save($hash, $user, $ip, $now, $now->modify('+' . $lifetime . ' seconds'));
    }

    public function testFindsAWorkingToken(): void
    {
        $this->save();

        self::assertSame('7', $this->store->findUserId('hash', $this->clock->now()));
        self::assertNull($this->store->findUserId('other', $this->clock->now()));
    }

    public function testAnExpiredTokenIsNotFound(): void
    {
        $this->save();
        $this->clock->advance(600);

        self::assertNull($this->store->findUserId('hash', $this->clock->now()));
        self::assertFalse($this->store->consume('hash', $this->clock->now()));
    }

    public function testATokenCanOnlyBeConsumedOnce(): void
    {
        $this->save();

        self::assertTrue($this->store->consume('hash', $this->clock->now()));
        self::assertFalse($this->store->consume('hash', $this->clock->now()));
        self::assertNull($this->store->findUserId('hash', $this->clock->now()));
    }

    public function testSavingReplacesTheUsersEarlierTokensOnly(): void
    {
        $this->save('first');
        $this->save('someone-else', '8');
        $this->save('second');

        self::assertNull($this->store->findUserId('first', $this->clock->now()));
        self::assertSame('7', $this->store->findUserId('second', $this->clock->now()));
        self::assertSame('8', $this->store->findUserId('someone-else', $this->clock->now()));
    }

    public function testInvalidatingStopsTheUsersTokens(): void
    {
        $this->save();

        $this->store->invalidateForUser('7', $this->clock->now());

        self::assertNull($this->store->findUserId('hash', $this->clock->now()));
    }

    public function testCountsIncludeUsedTokensWithinTheWindow(): void
    {
        $since = $this->clock->now()->modify('-1 second');
        $this->save('one');
        $this->save('two');
        $this->save('three', '8');
        $this->save('four', '9', '203.0.113.9');

        self::assertSame(2, $this->store->countForUser('7', $since));
        self::assertSame(3, $this->store->countForIpAddress('203.0.113.5', $since));
        self::assertSame(0, $this->store->countForUser('7', $this->clock->now()));
    }

    public function testPurgeRemovesOldRows(): void
    {
        $this->save('old');
        $this->clock->advance(100);
        $this->save('new', '8');

        $this->store->purge($this->clock->now()->modify('-50 seconds'));

        self::assertCount(1, $this->entityManager->getRepository(PasswordReminder::class)->findAll());
        self::assertSame('8', $this->store->findUserId('new', $this->clock->now()));
    }
}
