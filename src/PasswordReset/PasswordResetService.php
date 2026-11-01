<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\PasswordReset;

use FwsDoctrineAuth\Event\PasswordChanged;
use FwsDoctrineAuth\Event\PasswordResetDeliveryFailed;
use FwsDoctrineAuth\Event\PasswordResetRequested;
use FwsDoctrineAuth\Password\PasswordHasherInterface;
use FwsDoctrineAuth\Password\PasswordRules;
use FwsDoctrineAuth\Throttle\LoginThrottleInterface;
use FwsDoctrineAuth\User\UserProviderInterface;
use Psr\Clock\ClockInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Throwable;

use function bin2hex;
use function hash;
use function max;
use function random_bytes;

/**
 * Forgotten passwords. The application provides the pages and the email, through AuthMailerInterface.
 */
final class PasswordResetService
{
    /**
     * @param array{lifetime: int, window: int, account: array{max_requests: int}, ip: array{max_requests: int}} $limits
     */
    public function __construct(
        private readonly UserProviderInterface $users,
        private readonly PasswordHasherInterface $hasher,
        private readonly PasswordResetStoreInterface $store,
        private readonly AuthMailerInterface $mailer,
        private readonly LoginThrottleInterface $throttle,
        private readonly EventDispatcherInterface $events,
        private readonly ClockInterface $clock,
        private readonly array $limits,
    ) {
    }

    /**
     * Email a reset link if the identity belongs to an enabled account.
     *
     * Returns nothing, so that your page says the same thing whether or not the account exists. Mail failures are
     * reported through the PasswordResetDeliveryFailed event for the same reason, so listen for it to find out about them.
     */
    public function request(string $identity, string $ipAddress): void
    {
        foreach ($this->users->findByIdentity($identity) as $user) {
            if ($this->users->isEnabled($user)) {
                $this->issue($user, $ipAddress);
            }
        }
    }

    private function issue(object $user, string $ipAddress): void
    {
        $now    = $this->clock->now();
        $userId = $this->users->getId($user);

        $this->store->purge($now->modify('-' . max($this->limits['lifetime'], $this->limits['window']) . ' seconds'));

        if ($this->limitReached($userId, $ipAddress)) {
            return;
        }

        $token     = bin2hex(random_bytes(32));
        $expiresAt = $now->modify('+' . $this->limits['lifetime'] . ' seconds');
        $this->store->save($this->hashToken($token), $userId, $ipAddress, $now, $expiresAt);

        try {
            $this->mailer->sendPasswordReset($user, $token, $expiresAt);
        } catch (Throwable $exception) {
            $this->events->dispatch(new PasswordResetDeliveryFailed($user, $userId, $exception));

            return;
        }

        $this->events->dispatch(new PasswordResetRequested($user, $userId, $ipAddress));
    }

    /**
     * Stops the form being used to fill someone's inbox.
     */
    private function limitReached(string $userId, string $ipAddress): bool
    {
        $since      = $this->clock->now()->modify('-' . $this->limits['window'] . ' seconds');
        $maxAccount = $this->limits['account']['max_requests'];
        $maxIp      = $this->limits['ip']['max_requests'];

        return ($maxAccount > 0 && $this->store->countForUser($userId, $since) >= $maxAccount)
            || ($maxIp > 0 && $this->store->countForIpAddress($ipAddress, $since) >= $maxIp);
    }

    /**
     * The user a token belongs to, so your reset page can decide whether to show the form.
     */
    public function findUser(string $token): ?object
    {
        $userId = $this->store->findUserId($this->hashToken($token), $this->clock->now());
        if ($userId === null) {
            return null;
        }

        $user = $this->users->findById($userId);

        return $user !== null && $this->users->isEnabled($user) ? $user : null;
    }

    /**
     * Set a new password. Check it against your own password policy first.
     *
     * Every session the user has ends, and if they were locked out by failed logins, that ends too.
     */
    public function reset(string $token, string $newPassword, string $ipAddress): PasswordResetStatus
    {
        $user = $this->findUser($token);
        if ($user === null) {
            return PasswordResetStatus::InvalidToken;
        }

        if (! PasswordRules::isAcceptable($newPassword)) {
            return PasswordResetStatus::UnacceptablePassword;
        }

        $now = $this->clock->now();
        if (! $this->store->consume($this->hashToken($token), $now)) {
            return PasswordResetStatus::InvalidToken;
        }

        $userId = $this->users->getId($user);
        $this->users->setPasswordHash($user, $this->hasher->hash($newPassword));
        $this->store->invalidateForUser($userId, $now);
        $this->throttle->clear($this->users->getIdentity($user));
        $this->events->dispatch(new PasswordChanged($user, $userId, $ipAddress));

        return PasswordResetStatus::Success;
    }

    /**
     * The token has plenty of randomness, so a fast hash is enough and it can be looked up directly.
     * A database leak therefore gives nobody a working link.
     */
    private function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }
}
