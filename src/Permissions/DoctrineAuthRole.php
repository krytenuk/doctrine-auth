<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\Permissions;

use Laminas\Permissions\Acl\Role\GenericRole;

/**
 * GenericRole
 */
class DoctrineAuthRole extends GenericRole
{
    protected array $route;

    /**
     * Sets the Role identifier
     *
     * @param array $route
     */
    public function __construct(string $roleId, array $route = [])
    {
        parent::__construct($roleId);
        $this->setRoute($route);
    }

    /**
     * @param array $route
     */
    public function setRoute(array $route): DoctrineAuthRole
    {
        $this->route = $route;
        return $this;
    }

    /**
     * @return array
     */
    public function getRoute(): array
    {
        return $this->route;
    }
}
