<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\Mezzio;

/**
 * Supplies the roles that mezzio-authorization checks. The module does not manage roles itself.
 */
interface RolesProviderInterface
{
    /**
     * @return list<string>
     */
    public function getRoles(object $user): array;
}
