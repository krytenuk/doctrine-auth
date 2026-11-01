<?php

declare(strict_types=1);

namespace FwsDoctrineAuthTest\Doctrine;

use FwsDoctrineAuth\Doctrine\DoctrineLoginThrottle;
use FwsDoctrineAuth\Entity\FailedLoginAttemptsLog;
use FwsDoctrineAuth\Entity\IpBlocked;
use FwsDoctrineAuth\Exception\DoctrineAuthException;
use FwsDoctrineAuthTest\Support\DoctrineTestCase;
use FwsDoctrineAuthTest\Support\FakeClock;

final class DoctrineLoginThrottleTest extends DoctrineTestCase
{
    private const IP = '203.0.113.5';

    private FakeClock $clock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->clock = new FakeClock();
    }

    private function throttle(
        int $accountMax = 3,
        int $accountWindow = 60,
        int $ipMax = 5,
        int $ipWindow = 60,
        int $blockFor = 300,
    ): DoctrineLoginThrottle {
        return new DoctrineLoginThrottle(
            $this->entityManager,
            $this->clock,
            [
                'account' => ['max_attempts' => $accountMax, 'window' => $accountWindow],
                'ip'      => ['max_attempts' => $ipMax, 'window' => $ipWindow, 'block_for' => $blockFor],
            ],
            'test-secret'
        );
    }

    private function recordFailures(DoctrineLoginThrottle $throttle, int $times, string $identity = 'a@example.com', string $ip = self::IP): void
    {
        for ($i = 0; $i < $times; $i++) {
            $throttle->recordFailure($identity, $ip);
        }
    }

    public function testASecretIsRequired(): void
    {
        $this->expectException(DoctrineAuthException::class);

        new DoctrineLoginThrottle($this->entityManager, $this->clock, ['account' => [], 'ip' => []], '');
    }

    public function testAllowsAttemptsBelowTheAccountLimit(): void
    {
        $throttle = $this->throttle();
        $this->recordFailures($throttle, 2);

        self::assertFalse($throttle->check('a@example.com', self::IP)->blocked);
    }

    public function testTheAccountLimitBlocksFromAnyAddress(): void
    {
        $throttle = $this->throttle();
        $this->recordFailures($throttle, 1, ip: '203.0.113.1');
        $this->recordFailures($throttle, 1, ip: '203.0.113.2');
        $this->recordFailures($throttle, 1, ip: '203.0.113.3');

        $status = $throttle->check('a@example.com', '203.0.113.99');

        self::assertTrue($status->blocked);
        self::assertSame(60, $status->retryAfter);
    }

    public function testIdentityCaseAndSpacesCountAsTheSameAccount(): void
    {
        $throttle = $this->throttle();
        $throttle->recordFailure('a@example.com', self::IP);
        $throttle->recordFailure('A@EXAMPLE.COM', self::IP);
        $throttle->recordFailure(' a@example.com ', self::IP);

        self::assertTrue($throttle->check('a@example.com', self::IP)->blocked);
    }

    public function testOtherAccountsAreNotAffected(): void
    {
        $throttle = $this->throttle();
        $this->recordFailures($throttle, 3);

        self::assertFalse($throttle->check('b@example.com', self::IP)->blocked);
    }

    public function testTheAccountRecoversWhenAttemptsLeaveTheWindow(): void
    {
        $throttle = $this->throttle();
        $this->recordFailures($throttle, 3);

        $this->clock->advance(45);
        self::assertSame(15, $throttle->check('a@example.com', self::IP)->retryAfter);

        $this->clock->advance(15);
        self::assertFalse($throttle->check('a@example.com', self::IP)->blocked);
    }

    public function testClearingAnAccountForgetsItsFailuresOnly(): void
    {
        $throttle = $this->throttle();
        $this->recordFailures($throttle, 3);
        $this->recordFailures($throttle, 2, 'b@example.com', '203.0.113.6');

        $throttle->clear('a@example.com');

        self::assertFalse($throttle->check('a@example.com', self::IP)->blocked);
        self::assertSame(2, $this->entityManager->getRepository(FailedLoginAttemptsLog::class)->count([]));
    }

    public function testManyAccountsFromOneAddressBlockTheAddress(): void
    {
        $throttle = $this->throttle();
        foreach (['a', 'b', 'c', 'd', 'e'] as $name) {
            $throttle->recordFailure($name . '@example.com', self::IP);
        }

        $status = $throttle->check('someone-new@example.com', self::IP);

        self::assertTrue($status->blocked);
        self::assertSame(300, $status->retryAfter);
        self::assertFalse($throttle->check('someone-new@example.com', '203.0.113.99')->blocked);
    }

    public function testTheAddressBlockExpires(): void
    {
        $throttle = $this->throttle();
        foreach (['a', 'b', 'c', 'd', 'e'] as $name) {
            $throttle->recordFailure($name . '@example.com', self::IP);
        }

        $this->clock->advance(300);

        self::assertFalse($throttle->check('new@example.com', self::IP)->blocked);
    }

    public function testBlockForZeroNeverExpires(): void
    {
        $throttle = $this->throttle(blockFor: 0);
        foreach (['a', 'b', 'c', 'd', 'e'] as $name) {
            $throttle->recordFailure($name . '@example.com', self::IP);
        }

        $this->clock->advance(10000000);
        $status = $throttle->check('new@example.com', self::IP);

        self::assertTrue($status->blocked);
        self::assertNull($status->retryAfter);
    }

    public function testAMaximumOfZeroTurnsALimitOff(): void
    {
        $throttle = $this->throttle(accountMax: 0, ipMax: 0);
        $this->recordFailures($throttle, 20);

        self::assertFalse($throttle->check('a@example.com', self::IP)->blocked);
        self::assertSame(0, $this->entityManager->getRepository(IpBlocked::class)->count([]));
    }

    public function testOldRowsAreRemoved(): void
    {
        $throttle = $this->throttle();
        foreach (['a', 'b', 'c', 'd', 'e'] as $name) {
            $throttle->recordFailure($name . '@example.com', self::IP);
        }
        self::assertSame(1, $this->entityManager->getRepository(IpBlocked::class)->count([]));

        $this->clock->advance(301);
        $throttle->recordFailure('later@example.com', '203.0.113.99');

        self::assertSame(1, $this->entityManager->getRepository(FailedLoginAttemptsLog::class)->count([]));
        self::assertSame(0, $this->entityManager->getRepository(IpBlocked::class)->count([]));
    }

    public function testTheTypedIdentityIsNotStored(): void
    {
        $throttle = $this->throttle();
        $throttle->recordFailure('a@example.com', self::IP);

        $stored = $this->entityManager->getRepository(FailedLoginAttemptsLog::class)->findAll()[0]->getIdentityHash();

        self::assertStringNotContainsString('example', $stored);
        self::assertNotSame(hash('sha256', 'a@example.com'), $stored);
        self::assertSame(64, strlen($stored));
    }
}
