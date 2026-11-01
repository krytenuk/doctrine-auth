<?php

declare(strict_types=1);

namespace FwsDoctrineAuthTest\Support;

final class FakeUser
{
    public function __construct(
        public string $id,
        public string $identity,
        public ?string $hash,
        public bool $enabled = true,
    ) {
    }
}
