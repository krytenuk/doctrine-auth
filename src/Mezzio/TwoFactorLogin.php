<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\Mezzio;

use FwsDoctrineAuth\Event\LoginSucceeded;
use FwsDoctrineAuth\Event\LoginThrottled;
use FwsDoctrineAuth\Event\TwoFactorFailed;
use FwsDoctrineAuth\Throttle\LoginThrottleInterface;
use FwsDoctrineAuth\TwoFactor\TwoFactorMethods;
use FwsDoctrineAuth\TwoFactor\TwoFactorResult;
use FwsDoctrineAuth\User\UserProviderInterface;
use Mezzio\Session\SessionInterface;
use Psr\Clock\ClockInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

use function hash_equals;
use function in_array;
use function is_array;
use function is_int;
use function is_string;

/**
 * The second step of a login. After LoginService says a second factor is needed, begin() remembers the user in the
 * session, without logging them in. verify() logs them in once they enter a correct code.
 *
 * Wrong codes count as failed logins for the account and the address, so the usual limits apply to them too.
 */
final class TwoFactorLogin
{
    public const SESSION_KEY = 'fws_doctrine_auth.two_factor';

    /**
     * @param int $lifetime Seconds the user has to enter a code
     */
    public function __construct(
        private readonly UserProviderInterface $users,
        private readonly SessionLogin $sessionLogin,
        private readonly TwoFactorMethods $methods,
        private readonly LoginThrottleInterface $throttle,
        private readonly EventDispatcherInterface $events,
        private readonly ClockInterface $clock,
        private readonly int $lifetime,
    ) {
    }

    /**
     * Call this when LoginResult::requiresTwoFactor() is true. The session ID changes, as it does at login.
     *
     * @return SessionInterface The session to use from now on. See SessionLogin::login()
     */
    public function begin(SessionInterface $session, object $user): SessionInterface
    {
        $session->clear();
        $session = $session->regenerate();
        $session->set(self::SESSION_KEY, [
            'user_id'    => $this->users->getId($user),
            'credential' => $this->sessionLogin->credentialFingerprint($user),
            'started_at' => $this->clock->now()->getTimestamp(),
        ]);

        return $session;
    }

    /**
     * The user waiting to enter a code, or null if there is none or they took too long.
     */
    public function pendingUser(SessionInterface $session): ?object
    {
        $pending = $session->get(self::SESSION_KEY);
        if (
            ! is_array($pending)
            || ! is_string($pending['user_id'] ?? null)
            || ! is_string($pending['credential'] ?? null)
            || ! is_int($pending['started_at'] ?? null)
        ) {
            return null;
        }

        if ($this->clock->now()->getTimestamp() - $pending['started_at'] > $this->lifetime) {
            $this->cancel($session);

            return null;
        }

        $user = $this->users->findById($pending['user_id']);
        if (
            $user === null
            || ! $this->users->isEnabled($user)
            || ! hash_equals($this->sessionLogin->credentialFingerprint($user), $pending['credential'])
        ) {
            $this->cancel($session);

            return null;
        }

        return $user;
    }

    /**
     * The methods the waiting user can choose from, so your page can offer them.
     *
     * @return list<string>
     */
    public function availableMethods(SessionInterface $session): array
    {
        $user = $this->pendingUser($session);

        return $user === null ? [] : $this->methods->enabledFor($user);
    }

    /**
     * Call this when the user chooses a method, so that one which sends a code can send it.
     *
     * @return bool False if there is no login waiting, or the user cannot use that method
     */
    public function start(SessionInterface $session, string $method): bool
    {
        $user = $this->pendingUser($session);
        if ($user === null || ! in_array($method, $this->methods->enabledFor($user), true)) {
            return false;
        }

        $this->methods->get($method)?->start($user);

        return true;
    }

    /**
     * @param string $ipAddress The client's address. See LoginService::attempt()
     */
    public function verify(SessionInterface $session, string $method, string $code, string $ipAddress): TwoFactorResult
    {
        $user = $this->pendingUser($session);
        if ($user === null) {
            return TwoFactorResult::noPendingLogin();
        }

        $identity = $this->users->getIdentity($user);
        $status   = $this->throttle->check($identity, $ipAddress);
        if ($status->blocked) {
            $this->cancel($session);
            $this->events->dispatch(new LoginThrottled($identity, $ipAddress, $status->retryAfter));

            return TwoFactorResult::throttled($status->retryAfter);
        }

        $userId = $this->users->getId($user);
        if (! $this->isCorrect($user, $method, $code)) {
            $this->throttle->recordFailure($identity, $ipAddress);
            $this->events->dispatch(new TwoFactorFailed($user, $userId, $method, $ipAddress));

            $status = $this->throttle->check($identity, $ipAddress);
            if ($status->blocked) {
                $this->cancel($session);

                return TwoFactorResult::throttled($status->retryAfter);
            }

            return TwoFactorResult::invalidCode();
        }

        $this->throttle->clear($identity);
        $session = $this->sessionLogin->login($session, $user);
        $this->events->dispatch(new LoginSucceeded($user, $userId, $ipAddress));

        return TwoFactorResult::success($user, $session);
    }

    private function isCorrect(object $user, string $method, string $code): bool
    {
        return in_array($method, $this->methods->enabledFor($user), true)
            && (bool) $this->methods->get($method)?->verify($user, $code);
    }

    /**
     * Forget the waiting login, such as when the user chooses to start again.
     */
    public function cancel(SessionInterface $session): void
    {
        $session->unset(self::SESSION_KEY);
    }
}
