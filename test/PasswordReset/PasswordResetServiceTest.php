<?php

declare(strict_types=1);

namespace FwsDoctrineAuthTest\PasswordReset;

use FwsDoctrineAuth\Event\PasswordChanged;
use FwsDoctrineAuth\Event\PasswordResetDeliveryFailed;
use FwsDoctrineAuth\Event\PasswordResetRequested;
use FwsDoctrineAuth\PasswordReset\PasswordResetService;
use FwsDoctrineAuth\PasswordReset\PasswordResetStatus;
use FwsDoctrineAuthTest\Support\CountingHasher;
use FwsDoctrineAuthTest\Support\FakeClock;
use FwsDoctrineAuthTest\Support\FakeThrottle;
use FwsDoctrineAuthTest\Support\FakeUser;
use FwsDoctrineAuthTest\Support\InMemoryPasswordResetStore;
use FwsDoctrineAuthTest\Support\InMemoryUserProvider;
use FwsDoctrineAuthTest\Support\RecordingDispatcher;
use FwsDoctrineAuthTest\Support\RecordingMailer;
use PHPUnit\Framework\TestCase;

final class PasswordResetServiceTest extends TestCase
{
    private const IP = '203.0.113.5';

    private FakeUser $user;
    private CountingHasher $hasher;
    private InMemoryPasswordResetStore $store;
    private RecordingMailer $mailer;
    private FakeThrottle $throttle;
    private RecordingDispatcher $events;
    private FakeClock $clock;
    private InMemoryUserProvider $users;

    protected function setUp(): void
    {
        $this->hasher   = new CountingHasher();
        $this->user     = new FakeUser('7', 'user@example.com', $this->hasher->hash('old password'));
        $this->users    = new InMemoryUserProvider($this->user);
        $this->store    = new InMemoryPasswordResetStore();
        $this->mailer   = new RecordingMailer();
        $this->throttle = new FakeThrottle();
        $this->events   = new RecordingDispatcher();
        $this->clock    = new FakeClock();
    }

    private function service(int $maxAccount = 3, int $maxIp = 10): PasswordResetService
    {
        return new PasswordResetService(
            $this->users,
            $this->hasher,
            $this->store,
            $this->mailer,
            $this->throttle,
            $this->events,
            $this->clock,
            ['lifetime' => 600, 'window' => 3600, 'account' => ['max_requests' => $maxAccount], 'ip' => ['max_requests' => $maxIp]],
        );
    }

    public function testARequestEmailsAToken(): void
    {
        $this->service()->request('user@example.com', self::IP);

        self::assertCount(1, $this->mailer->sent);
        self::assertSame($this->user, $this->mailer->sent[0]['user']);
        self::assertEquals($this->clock->now()->modify('+600 seconds'), $this->mailer->sent[0]['expiresAt']);
        self::assertSame([PasswordResetRequested::class], $this->events->classes());
    }

    public function testTheTokenIsLongRandomAndNotStored(): void
    {
        $this->service()->request('user@example.com', self::IP);
        $this->clock->advance(1);
        $this->service()->request('user@example.com', self::IP);

        $first  = $this->mailer->sent[0]['token'];
        $second = $this->mailer->sent[1]['token'];
        self::assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $first);
        self::assertNotSame($first, $second);
        self::assertArrayNotHasKey($second, $this->store->rows);
        self::assertArrayHasKey(hash('sha256', $second), $this->store->rows);
    }

    public function testAnUnknownIdentityDoesNothingVisible(): void
    {
        $this->service()->request('nobody@example.com', self::IP);

        self::assertSame([], $this->mailer->sent);
        self::assertSame([], $this->store->rows);
    }

    public function testADisabledUserGetsNoEmail(): void
    {
        $this->user->enabled = false;

        $this->service()->request('user@example.com', self::IP);

        self::assertSame([], $this->mailer->sent);
    }

    public function testRequestsPerAccountAreLimited(): void
    {
        $service = $this->service(maxAccount: 2);
        for ($i = 0; $i < 4; $i++) {
            $this->clock->advance(1);
            $service->request('user@example.com', self::IP);
        }

        self::assertCount(2, $this->mailer->sent);
    }

    public function testRequestsPerAddressAreLimited(): void
    {
        $other              = new FakeUser('8', 'other@example.com', 'hash');
        $this->users->users = [$this->user, $other];

        $service = $this->service(maxIp: 1);
        $service->request('user@example.com', self::IP);
        $service->request('other@example.com', self::IP);

        self::assertCount(1, $this->mailer->sent);
    }

    public function testTheLimitsRecoverAfterTheWindow(): void
    {
        $service = $this->service(maxAccount: 1);
        $service->request('user@example.com', self::IP);

        $this->clock->advance(3601);
        $service->request('user@example.com', self::IP);

        self::assertCount(2, $this->mailer->sent);
    }

    public function testAMaximumOfZeroTurnsTheLimitsOff(): void
    {
        $service = $this->service(maxAccount: 0, maxIp: 0);
        for ($i = 0; $i < 20; $i++) {
            $service->request('user@example.com', self::IP);
        }

        self::assertCount(20, $this->mailer->sent);
    }

    public function testAMailFailureIsReportedByEventNotToTheCaller(): void
    {
        $this->mailer->fail = true;

        $this->service()->request('user@example.com', self::IP);

        self::assertSame([PasswordResetDeliveryFailed::class], $this->events->classes());
        self::assertSame('Mail server down', $this->events->events[0]->exception->getMessage());
    }

    public function testFindUserAcceptsOnlyAWorkingToken(): void
    {
        $service = $this->service();
        $service->request('user@example.com', self::IP);

        self::assertSame($this->user, $service->findUser($this->mailer->lastToken()));
        self::assertNull($service->findUser('wrong'));
    }

    public function testAResetChangesThePassword(): void
    {
        $service = $this->service();
        $service->request('user@example.com', self::IP);
        $old = $this->user->hash;

        $status = $service->reset($this->mailer->lastToken(), 'new password', self::IP);

        self::assertSame(PasswordResetStatus::Success, $status);
        self::assertNotSame($old, $this->user->hash);
        self::assertTrue($this->hasher->verify('new password', (string) $this->user->hash));
        self::assertSame(PasswordChanged::class, $this->events->classes()[1]);
    }

    public function testATokenWorksOnce(): void
    {
        $service = $this->service();
        $service->request('user@example.com', self::IP);
        $token = $this->mailer->lastToken();

        $service->reset($token, 'new password', self::IP);

        self::assertSame(PasswordResetStatus::InvalidToken, $service->reset($token, 'another', self::IP));
        self::assertTrue($this->hasher->verify('new password', (string) $this->user->hash));
    }

    public function testAnExpiredTokenFails(): void
    {
        $service = $this->service();
        $service->request('user@example.com', self::IP);

        $this->clock->advance(600);

        self::assertNull($service->findUser($this->mailer->lastToken()));
        self::assertSame(PasswordResetStatus::InvalidToken, $service->reset($this->mailer->lastToken(), 'new password', self::IP));
    }

    public function testANewRequestStopsTheOldTokenWorking(): void
    {
        $service = $this->service();
        $service->request('user@example.com', self::IP);
        $old = $this->mailer->lastToken();
        $service->request('user@example.com', self::IP);

        self::assertNull($service->findUser($old));
        self::assertSame($this->user, $service->findUser($this->mailer->lastToken()));
    }

    public function testAnUnacceptablePasswordKeepsTheTokenWorking(): void
    {
        $service = $this->service();
        $service->request('user@example.com', self::IP);
        $token = $this->mailer->lastToken();
        $old   = $this->user->hash;

        self::assertSame(PasswordResetStatus::UnacceptablePassword, $service->reset($token, '', self::IP));
        self::assertSame(PasswordResetStatus::UnacceptablePassword, $service->reset($token, "with\0null", self::IP));
        self::assertSame($old, $this->user->hash);
        self::assertSame(PasswordResetStatus::Success, $service->reset($token, 'new password', self::IP));
    }

    public function testAUserDisabledAfterTheRequestCannotReset(): void
    {
        $service = $this->service();
        $service->request('user@example.com', self::IP);
        $this->user->enabled = false;

        self::assertSame(PasswordResetStatus::InvalidToken, $service->reset($this->mailer->lastToken(), 'new password', self::IP));
    }

    public function testAResetEndsAFailedLoginLockout(): void
    {
        $service = $this->service();
        $service->request('user@example.com', self::IP);

        $service->reset($this->mailer->lastToken(), 'new password', self::IP);

        self::assertSame(['user@example.com'], $this->throttle->cleared);
    }

    /**
     * Two requests can both pass the check that a token works. Only the one that consumes it may change the password.
     */
    public function testLosingARaceToUseATokenChangesNothing(): void
    {
        $service = $this->service();
        $service->request('user@example.com', self::IP);
        $old = $this->user->hash;

        $this->store->loseConsumeRace = true;

        self::assertSame(PasswordResetStatus::InvalidToken, $service->reset($this->mailer->lastToken(), 'new password', self::IP));
        self::assertSame($old, $this->user->hash);
        self::assertNotContains(PasswordChanged::class, $this->events->classes());
    }

    public function testOldRowsAreRemoved(): void
    {
        $service = $this->service();
        $service->request('user@example.com', self::IP);

        $this->clock->advance(3601);
        $service->request('user@example.com', self::IP);

        self::assertCount(1, $this->store->rows);
    }
}
