<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\Event;

/**
 * The identity is what was typed. See LoginFailed.
 */
final class LoginThrottled
{
    public function __construct(
        public readonly string $identity,
        public readonly string $ipAddress,
        public readonly ?int $retryAfter,
    ) {
    }
}
