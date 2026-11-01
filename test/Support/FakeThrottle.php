<?php

declare(strict_types=1);

namespace FwsDoctrineAuthTest\Support;

use FwsDoctrineAuth\Throttle\LoginThrottleInterface;
use FwsDoctrineAuth\Throttle\ThrottleStatus;

final class FakeThrottle implements LoginThrottleInterface
{
    /** @var list<array{0: string, 1: string}> */
    public array $failures = [];

    /** @var list<string> */
    public array $cleared = [];

    public bool $blocked = false;

    public ?int $blockAfterFailures = null;

    public function check(string $identity, string $ipAddress): ThrottleStatus
    {
        if ($this->blocked) {
            return ThrottleStatus::blocked(30);
        }

        if ($this->blockAfterFailures !== null && count($this->failures) >= $this->blockAfterFailures) {
            return ThrottleStatus::blocked(60);
        }

        return ThrottleStatus::allowed();
    }

    public function recordFailure(string $identity, string $ipAddress): void
    {
        $this->failures[] = [$identity, $ipAddress];
    }

    public function clear(string $identity): void
    {
        $this->cleared[] = $identity;
    }
}
