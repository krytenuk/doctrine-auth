<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\Event;

use Throwable;

/**
 * The mailer threw an exception. The caller is not told, as that would reveal the account exists.
 */
final class PasswordResetDeliveryFailed
{
    public function __construct(
        public readonly object $user,
        public readonly string $userId,
        public readonly Throwable $exception,
    ) {
    }
}
