<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\Throttle;

final class ThrottleStatus
{
    /**
     * @param int|null $retryAfter Seconds until login attempts are allowed again, null if blocked with no end
     */
    private function __construct(public readonly bool $blocked, public readonly ?int $retryAfter)
    {
    }

    public static function allowed(): self
    {
        return new self(false, null);
    }

    public static function blocked(?int $retryAfter): self
    {
        return new self(true, $retryAfter);
    }
}
