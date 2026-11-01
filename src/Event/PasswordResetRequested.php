<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\Event;

final class PasswordResetRequested
{
    public function __construct(
        public readonly object $user,
        public readonly string $userId,
        public readonly string $ipAddress,
    ) {
    }
}
