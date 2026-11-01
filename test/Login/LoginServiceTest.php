<?php

declare(strict_types=1);

namespace FwsDoctrineAuthTest\Login;

use FwsDoctrineAuth\Event\LoginFailed;
use FwsDoctrineAuth\Event\LoginSucceeded;
use FwsDoctrineAuth\Event\LoginThrottled;
use FwsDoctrineAuth\Login\LoginService;
use FwsDoctrineAuth\Login\LoginStatus;
use FwsDoctrineAuth\Password\NativePasswordHasher;
use FwsDoctrineAuth\TwoFactor\NoTwoFactorPolicy;
use FwsDoctrineAuth\TwoFactor\TwoFactorPolicyInterface;
use FwsDoctrineAuthTest\Support\CountingHasher;
use FwsDoctrineAuthTest\Support\FakeThrottle;
use FwsDoctrineAuthTest\Support\FakeUser;
use FwsDoctrineAuthTest\Support\InMemoryUserProvider;
use FwsDoctrineAuthTest\Support\RecordingDispatcher;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class LoginServiceTest extends TestCase
{
    private const IP = '203.0.113.5';

    private InMemoryUserProvider $users;
    private CountingHasher $hasher;
    private FakeThrottle $throttle;
    private RecordingDispatcher $events;
    private FakeUser $user;

    protected function setUp(): void
    {
        $this->hasher   = new CountingHasher();
        $this->throttle = new FakeThrottle();
        $this->events   = new RecordingDispatcher();
        $this->user     = new FakeUser('7', 'user@example.com', $this->hasher->hash('correct'));
        $this->users    = new InMemoryUserProvider($this->user);
        $this->hasher->hashes = 0;
    }

    private function service(?TwoFactorPolicyInterface $twoFactor = null): LoginService
    {
        return new LoginService(
            $this->users,
            $this->hasher,
            $this->throttle,
            $twoFactor ?? new NoTwoFactorPolicy(),
            $this->events,
        );
    }

    public function testCorrectDetailsSucceed(): void
    {
        $result = $this->service()->attempt('user@example.com', 'correct', self::IP);

        self::assertTrue($result->isSuccess());
        self::assertSame($this->user, $result->user);
        self::assertSame(['user@example.com'], $this->throttle->cleared);
        self::assertSame([], $this->throttle->failures);
    }

    public function testSuccessDispatchesAnEventForTheLoginLog(): void
    {
        $this->service()->attempt('user@example.com', 'correct', self::IP);

        self::assertSame([LoginSucceeded::class], $this->events->classes());
        self::assertSame('7', $this->events->events[0]->userId);
        self::assertSame(self::IP, $this->events->events[0]->ipAddress);
    }

    public function testWrongPasswordFails(): void
    {
        $result = $this->service()->attempt('user@example.com', 'wrong', self::IP);

        self::assertSame(LoginStatus::InvalidCredentials, $result->status);
        self::assertNull($result->user);
        self::assertSame([['user@example.com', self::IP]], $this->throttle->failures);
        self::assertSame([LoginFailed::class], $this->events->classes());
    }

    /**
     * Version 1 accepted a value equal to the stored hash, so a database leak gave access to every account.
     */
    public function testTheStoredHashIsNotAcceptedAsThePassword(): void
    {
        $result = $this->service()->attempt('user@example.com', (string) $this->user->hash, self::IP);

        self::assertSame(LoginStatus::InvalidCredentials, $result->status);
    }

    public function testUnknownUserLooksLikeAWrongPasswordAndCostsAHash(): void
    {
        $result = $this->service()->attempt('nobody@example.com', 'whatever', self::IP);

        self::assertSame(LoginStatus::InvalidCredentials, $result->status);
        self::assertSame(1, $this->hasher->hashes);
        self::assertSame(0, $this->hasher->verifies);
        self::assertCount(1, $this->throttle->failures);
    }

    public function testKnownUserWithWrongPasswordCostsOneVerify(): void
    {
        $this->service()->attempt('user@example.com', 'wrong', self::IP);

        self::assertSame(0, $this->hasher->hashes);
        self::assertSame(1, $this->hasher->verifies);
    }

    public function testUserWithoutAPasswordCannotLogIn(): void
    {
        $this->user->hash = null;

        $result = $this->service()->attempt('user@example.com', '', self::IP);
        self::assertSame(LoginStatus::InvalidCredentials, $result->status);

        $result = $this->service()->attempt('user@example.com', 'anything', self::IP);
        self::assertSame(LoginStatus::InvalidCredentials, $result->status);
        self::assertSame(1, $this->hasher->hashes);
    }

    public function testDisabledUserLooksLikeAWrongPassword(): void
    {
        $this->user->enabled = false;

        $result = $this->service()->attempt('user@example.com', 'correct', self::IP);

        self::assertSame(LoginStatus::InvalidCredentials, $result->status);
        self::assertNull($result->user);
        self::assertSame(1, $this->hasher->verifies);
        self::assertSame([LoginFailed::class], $this->events->classes());
        self::assertSame([], $this->throttle->cleared);
    }

    public function testCollidingUsersAreEachTried(): void
    {
        $other              = new FakeUser('8', 'user@example.com', $this->hasher->hash('other'));
        $this->users->users = [$this->user, $other];

        $result = $this->service()->attempt('user@example.com', 'other', self::IP);

        self::assertSame($other, $result->user);
    }

    public function testBlockedClientIsRefusedWithoutCheckingThePassword(): void
    {
        $this->throttle->blocked = true;

        $result = $this->service()->attempt('user@example.com', 'correct', self::IP);

        self::assertTrue($result->isThrottled());
        self::assertSame(30, $result->retryAfter);
        self::assertSame(0, $this->hasher->verifies);
        self::assertSame([LoginThrottled::class], $this->events->classes());
    }

    public function testTheFailureThatReachesTheLimitReportsTheBlock(): void
    {
        $this->throttle->blockAfterFailures = 1;

        $result = $this->service()->attempt('user@example.com', 'wrong', self::IP);

        self::assertTrue($result->isThrottled());
        self::assertSame(60, $result->retryAfter);
    }

    public function testTwoFactorHoldsBackTheLogin(): void
    {
        $policy = new class implements TwoFactorPolicyInterface {
            public function requiresTwoFactor(object $user): bool
            {
                return true;
            }
        };

        $result = $this->service($policy)->attempt('user@example.com', 'correct', self::IP);

        self::assertTrue($result->requiresTwoFactor());
        self::assertFalse($result->isSuccess());
        self::assertSame($this->user, $result->user);
        self::assertSame([], $this->events->events);
    }

    public function testOldHashIsUpgradedAtLogin(): void
    {
        $this->user->hash = (new NativePasswordHasher(PASSWORD_BCRYPT, ['cost' => 5]))->hash('correct');
        $old              = $this->user->hash;

        $result = $this->service()->attempt('user@example.com', 'correct', self::IP);

        self::assertTrue($result->isSuccess());
        self::assertNotSame($old, $this->user->hash);
        self::assertStringStartsWith('$2y$04$', (string) $this->user->hash);
    }

    public function testCurrentHashIsLeftAlone(): void
    {
        $hash = $this->user->hash;

        $this->service()->attempt('user@example.com', 'correct', self::IP);

        self::assertSame($hash, $this->user->hash);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function unacceptablePasswords(): array
    {
        return [
            'empty'     => [''],
            'null byte' => ["correct\0"],
            'too long'  => [str_repeat('a', 4097)],
        ];
    }

    #[DataProvider('unacceptablePasswords')]
    public function testUnacceptablePasswordsFailWithoutHashing(string $password): void
    {
        $result = $this->service()->attempt('user@example.com', $password, self::IP);

        self::assertSame(LoginStatus::InvalidCredentials, $result->status);
        self::assertSame(0, $this->hasher->hashes);
        self::assertSame(0, $this->hasher->verifies);
        self::assertCount(1, $this->throttle->failures);
    }
}
