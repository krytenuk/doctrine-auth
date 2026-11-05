<?php

declare(strict_types=1);

namespace FwsDoctrineAuthTest\Mezzio;

use FwsDoctrineAuth\Event\LoginSucceeded;
use FwsDoctrineAuth\Event\LoginThrottled;
use FwsDoctrineAuth\Event\TwoFactorFailed;
use FwsDoctrineAuth\Mezzio\SessionLogin;
use FwsDoctrineAuth\Mezzio\TwoFactorLogin;
use FwsDoctrineAuth\TwoFactor\TwoFactorMethods;
use FwsDoctrineAuth\TwoFactor\TwoFactorStatus;
use FwsDoctrineAuthTest\Support\FakeClock;
use FwsDoctrineAuthTest\Support\FakeThrottle;
use FwsDoctrineAuthTest\Support\FakeTwoFactorMethod;
use FwsDoctrineAuthTest\Support\FakeUser;
use FwsDoctrineAuthTest\Support\InMemoryUserProvider;
use FwsDoctrineAuthTest\Support\RecordingDispatcher;
use Laminas\ServiceManager\ServiceManager;
use Mezzio\Session\Session;
use Mezzio\Session\SessionInterface;
use PHPUnit\Framework\TestCase;

final class TwoFactorLoginTest extends TestCase
{
    private const IP = '203.0.113.5';

    private FakeUser $user;
    private InMemoryUserProvider $users;
    private FakeTwoFactorMethod $method;
    private FakeThrottle $throttle;
    private RecordingDispatcher $events;
    private FakeClock $clock;
    private TwoFactorLogin $twoFactor;

    protected function setUp(): void
    {
        $this->user      = new FakeUser('7', 'user@example.com', 'hash');
        $this->users     = new InMemoryUserProvider($this->user, new FakeUser('8', 'other@example.com', 'hash'));
        $this->method    = new FakeTwoFactorMethod();
        $this->throttle  = new FakeThrottle();
        $this->events    = new RecordingDispatcher();
        $this->clock     = new FakeClock();
        $this->twoFactor = new TwoFactorLogin(
            $this->users,
            new SessionLogin($this->users),
            new TwoFactorMethods(new ServiceManager(['services' => ['app' => $this->method]]), ['app' => 'app']),
            $this->throttle,
            $this->events,
            $this->clock,
            300,
        );
    }

    private function pendingSession(): SessionInterface
    {
        return $this->twoFactor->begin(new Session(['from-before' => 'value']), $this->user);
    }

    public function testBeginRegeneratesTheSessionWithoutLoggingIn(): void
    {
        $session = $this->pendingSession();

        self::assertTrue($session->isRegenerated());
        self::assertFalse($session->has('from-before'));
        self::assertFalse($session->has(SessionLogin::SESSION_KEY));
        self::assertSame($this->user, $this->twoFactor->pendingUser($session));
    }

    public function testTheRightCodeLogsTheUserIn(): void
    {
        $result = $this->twoFactor->verify($this->pendingSession(), 'app', '123456', self::IP);

        self::assertTrue($result->isSuccess());
        self::assertSame($this->user, $result->user);
        self::assertSame('7', $result->session?->get(SessionLogin::SESSION_KEY));
        self::assertFalse($result->session->has(TwoFactorLogin::SESSION_KEY));
        self::assertTrue($result->session->isRegenerated());
        self::assertSame([LoginSucceeded::class], $this->events->classes());
        self::assertSame(['user@example.com'], $this->throttle->cleared);
    }

    public function testAWrongCodeCountsAsAFailedLogin(): void
    {
        $session = $this->pendingSession();

        $result = $this->twoFactor->verify($session, 'app', '000000', self::IP);

        self::assertSame(TwoFactorStatus::InvalidCode, $result->status);
        self::assertSame([['user@example.com', self::IP]], $this->throttle->failures);
        self::assertSame([TwoFactorFailed::class], $this->events->classes());
        self::assertSame('app', $this->events->events[0]->method);
        self::assertFalse($session->has(SessionLogin::SESSION_KEY));
        self::assertSame($this->user, $this->twoFactor->pendingUser($session), 'The user can try again');
    }

    public function testTooManyWrongCodesEndThePendingLogin(): void
    {
        $this->throttle->blockAfterFailures = 2;
        $session = $this->pendingSession();

        $this->twoFactor->verify($session, 'app', '000000', self::IP);
        $result = $this->twoFactor->verify($session, 'app', '000000', self::IP);

        self::assertSame(TwoFactorStatus::Throttled, $result->status);
        self::assertSame(60, $result->retryAfter);
        self::assertNull($this->twoFactor->pendingUser($session));
    }

    public function testAThrottledAccountCannotUseEvenTheRightCode(): void
    {
        $session = $this->pendingSession();
        $this->throttle->blocked = true;

        $result = $this->twoFactor->verify($session, 'app', '123456', self::IP);

        self::assertSame(TwoFactorStatus::Throttled, $result->status);
        self::assertSame([LoginThrottled::class], $this->events->classes());
        self::assertFalse($session->has(SessionLogin::SESSION_KEY));
    }

    public function testAMethodTheUserHasNotSetUpIsRefused(): void
    {
        $this->method->enabledFor = [];
        $session                  = $this->pendingSession();

        self::assertSame(TwoFactorStatus::InvalidCode, $this->twoFactor->verify($session, 'app', '123456', self::IP)->status);
        self::assertSame(TwoFactorStatus::InvalidCode, $this->twoFactor->verify($session, 'sms', '123456', self::IP)->status);
        self::assertCount(2, $this->throttle->failures);
    }

    public function testWithoutAPendingLoginNothingHappens(): void
    {
        $result = $this->twoFactor->verify(new Session([]), 'app', '123456', self::IP);

        self::assertSame(TwoFactorStatus::NoPendingLogin, $result->status);
        self::assertSame([], $this->throttle->failures);
    }

    public function testThePendingLoginExpires(): void
    {
        $session = $this->pendingSession();
        $this->clock->advance(301);

        self::assertSame(TwoFactorStatus::NoPendingLogin, $this->twoFactor->verify($session, 'app', '123456', self::IP)->status);
        self::assertFalse($session->has(TwoFactorLogin::SESSION_KEY));
    }

    public function testChangingThePasswordEndsThePendingLogin(): void
    {
        $session          = $this->pendingSession();
        $this->user->hash = 'new-hash';

        self::assertNull($this->twoFactor->pendingUser($session));
    }

    public function testDisablingTheUserEndsThePendingLogin(): void
    {
        $session             = $this->pendingSession();
        $this->user->enabled = false;

        self::assertNull($this->twoFactor->pendingUser($session));
    }

    public function testStartTellsTheMethodSoItCanSendACode(): void
    {
        $session = $this->pendingSession();

        self::assertSame(['app'], $this->twoFactor->availableMethods($session));
        self::assertTrue($this->twoFactor->start($session, 'app'));
        self::assertFalse($this->twoFactor->start($session, 'sms'));
        self::assertFalse($this->twoFactor->start(new Session([]), 'app'));
        self::assertSame(['7'], $this->method->started);
    }

    public function testCancelForgetsThePendingLogin(): void
    {
        $session = $this->pendingSession();

        $this->twoFactor->cancel($session);

        self::assertNull($this->twoFactor->pendingUser($session));
        self::assertSame([], $this->twoFactor->availableMethods($session));
    }

    public function testATamperedSessionIsIgnored(): void
    {
        $session = new Session([TwoFactorLogin::SESSION_KEY => ['user_id' => '7']]);

        self::assertNull($this->twoFactor->pendingUser($session));
    }
}
