<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\Throttle;

interface LoginThrottleInterface
{
    public function check(string $identity, string $ipAddress): ThrottleStatus;

    public function recordFailure(string $identity, string $ipAddress): void;

    /**
     * Forget an account's failed attempts after a successful login. Failures counted against the IP address remain.
     */
    public function clear(string $identity): void;
}
