<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\Event;

/**
 * The identity is what was typed, so it may not belong to a user and may even be a mistyped password.
 * Take care if you log it.
 */
final class LoginFailed
{
    public function __construct(
        public readonly string $identity,
        public readonly string $ipAddress,
    ) {
    }
}
