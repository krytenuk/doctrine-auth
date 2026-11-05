<?php

declare(strict_types=1);

namespace FwsDoctrineAuthTest\Support;

use FwsDoctrineAuth\TwoFactor\TwoFactorMethodInterface;

use function in_array;

final class FakeTwoFactorMethod implements TwoFactorMethodInterface
{
    /** @var list<string> */
    public array $started = [];

    /**
     * @param list<string> $enabledFor User IDs that have set this method up
     */
    public function __construct(public array $enabledFor = ['7'], public string $code = '123456')
    {
    }

    public function isEnabledFor(object $user): bool
    {
        return in_array((string) $user->id, $this->enabledFor, true);
    }

    public function start(object $user): void
    {
        $this->started[] = (string) $user->id;
    }

    public function verify(object $user, string $code): bool
    {
        return $code === $this->code;
    }
}
