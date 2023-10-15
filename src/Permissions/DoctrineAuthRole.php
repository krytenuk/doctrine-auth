<?php

namespace FwsDoctrineAuth\Permissions;

use Laminas\Permissions\Acl\Role\GenericRole;

/**
 * GenericRole
 *
 * @author Garry Childs <info@freedomwebservices.net>
 */
class DoctrineAuthRole extends GenericRole
{
    protected array $route;

    /**
     * Sets the Role identifier
     *
     * @param string $roleId
     * @param array $route
     */
    public function __construct(string $roleId, array $route = [])
    {
        parent::__construct($roleId);
        $this->setRoute($route);
    }

    /**
     * @param array $route
     * @return DoctrineAuthRole
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
