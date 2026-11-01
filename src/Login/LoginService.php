<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\Login;

use FwsDoctrineAuth\Event\LoginFailed;
use FwsDoctrineAuth\Event\LoginSucceeded;
use FwsDoctrineAuth\Event\LoginThrottled;
use FwsDoctrineAuth\Password\PasswordHasherInterface;
use FwsDoctrineAuth\Password\PasswordRules;
use FwsDoctrineAuth\Throttle\LoginThrottleInterface;
use FwsDoctrineAuth\TwoFactor\TwoFactorPolicyInterface;
use FwsDoctrineAuth\User\UserProviderInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Checks login details and reports the outcome. It knows nothing about requests, sessions or pages, so the
 * application decides what to do with the result.
 */
final class LoginService
{
    public function __construct(
        private readonly UserProviderInterface $users,
        private readonly PasswordHasherInterface $hasher,
        private readonly LoginThrottleInterface $throttle,
        private readonly TwoFactorPolicyInterface $twoFactor,
        private readonly EventDispatcherInterface $events,
    ) {
    }

    /**
     * @param string $ipAddress The client's address. How to find it depends on your proxies, so the application decides
     */
    public function attempt(string $identity, string $password, string $ipAddress): LoginResult
    {
        $status = $this->throttle->check($identity, $ipAddress);
        if ($status->blocked) {
            $this->events->dispatch(new LoginThrottled($identity, $ipAddress, $status->retryAfter));

            return LoginResult::throttled($status->retryAfter);
        }

        $user = $this->verify($identity, $password);
        if ($user === null || ! $this->users->isEnabled($user)) {
            return $this->fail($identity, $ipAddress);
        }

        $this->upgradeHash($user, $password);
        $this->throttle->clear($identity);

        if ($this->twoFactor->requiresTwoFactor($user)) {
            return LoginResult::twoFactorRequired($user);
        }

        $this->events->dispatch(new LoginSucceeded($user, $this->users->getId($user), $ipAddress));

        return LoginResult::success($user);
    }

    private function verify(string $identity, string $password): ?object
    {
        if (! PasswordRules::isAcceptable($password)) {
            return null;
        }

        $checked = false;
        foreach ($this->users->findByIdentity($identity) as $user) {
            $hash = $this->users->getPasswordHash($user);
            if ($hash === null) {
                continue;
            }

            $checked = true;
            if ($this->hasher->verify($password, $hash)) {
                return $user;
            }
        }

        /** Spend the same time on an unknown user as on a known one, so response times do not reveal which exist */
        if (! $checked) {
            $this->hasher->hash($password);
        }

        return null;
    }

    private function upgradeHash(object $user, string $password): void
    {
        $hash = $this->users->getPasswordHash($user);
        if ($hash !== null && $this->hasher->needsRehash($hash)) {
            $this->users->setPasswordHash($user, $this->hasher->hash($password));
        }
    }

    private function fail(string $identity, string $ipAddress): LoginResult
    {
        $this->throttle->recordFailure($identity, $ipAddress);
        $this->events->dispatch(new LoginFailed($identity, $ipAddress));

        $status = $this->throttle->check($identity, $ipAddress);

        return $status->blocked ? LoginResult::throttled($status->retryAfter) : LoginResult::invalidCredentials();
    }
}
