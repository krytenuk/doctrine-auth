<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\Event;

/**
 * A good time to tell the user by email, in case it was not them.
 */
final class PasswordChanged
{
    public function __construct(
        public readonly object $user,
        public readonly string $userId,
        public readonly string $ipAddress,
    ) {
    }
}
