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
    /**
     *
     * @var array
     */
    protected $route;

    /**
     * Sets the Role identifier
     *
     * @param string $roleId
     */
    public function __construct($roleId, Array $route = [])
    {
        parent::__construct($roleId);
        $this->setRoute($route);
    }
    
    public function setRoute(Array $route)
    {
        $this->route = $route;
    }
    
    /**
     * 
     * @return array
     */
    public function getRoute() : Array
    {
        return $this->route;
    }
}
