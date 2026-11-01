<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\Mezzio;

final class NoRolesProvider implements RolesProviderInterface
{
    public function getRoles(object $user): array
    {
        return [];
    }
}
