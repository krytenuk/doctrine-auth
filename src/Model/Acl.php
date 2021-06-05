<?php

namespace FwsDoctrineAuth\Model;

use Laminas\Permissions\Acl\Acl as LaminasAcl;
use FwsDoctrineAuth\Permissions\DoctrineAuthRole as Role;
use Laminas\Permissions\Acl\Resource\GenericResource as Rescource;
use FwsDoctrineAuth\Exception\DoctrineAuthException;

/**
 * Build Access Control List from config array
 *
 * @author Garry Childs <info@freedomwebservices.net>
 */
class Acl extends LaminasAcl
{

    /**
     *
     * @var string
     */
    private $defaultRole;

    /**
     *
     * @var Role
     */
    private $defaultRegistrationRole;

    /**
     * Get the default role
     * This is the role of a user not logged in
     * @return string
     */
    public function getDefultRole()
    {
        return $this->defaultRole;
    }

    /**
     * Setup access control list
     * @param array $config
     * @throws DoctrineAuthException
     */
    public function __construct(Array $config)
    {
        if (array_key_exists('doctrineAuthAcl', $config)) {
            $this->addRoles($config);
            $this->addResources($config);
            $this->addPermissions($config);
        } else {
            throw new DoctrineAuthException('No ACL key found in config "doctrineAuthAcl"');
        }
    }

    /**
     * Add ACL roles
     * @param array $config
     * @throws DoctrineAuthException
     */
    private function addRoles(Array $config)
    {
        if (array_key_exists('roles', $config['doctrineAuthAcl'])) {
            $this->defaultRole = '';
            foreach ($config['doctrineAuthAcl']['roles'] as $role) {
                $redirect = [];
                if (array_key_exists('redirect', $role)) {
                    $redirect = $role['redirect'];
                } else {
                    if ($role['id'] != 'guest') {
                        throw new DoctrineAuthException(sprintf("Role's default redirect not defined in config for \"%s\"", $role['id']));
                    }
                }
                $this->addRole(new Role($role['id'], $redirect), $role['parents']);
                if (array_key_exists('default', $role) && $role['default'] === TRUE) {
                    $this->defaultRole = $role['id'];
                }
            }
            if (empty($this->defaultRole)) {
                throw new DoctrineAuthException('Default role not defined');
            }
        } else {
            throw new DoctrineAuthException('No roles found in config');
        }
        if (isset($config['doctrineAuth']['allowRegistration']) && $config['doctrineAuth']['allowRegistration'] === TRUE) {
            if (array_key_exists('defaultRegisterRole', $config['doctrineAuthAcl'])) {
                if ($this->hasRole($config['doctrineAuthAcl']['defaultRegisterRole'])) {
                    $this->defaultRegistrationRole = $this->getRole($config['doctrineAuthAcl']['defaultRegisterRole']);
                } else {
                    throw new DoctrineAuthException(sprintf('Registration role "%s" not found', $config['doctrineAuthAcl']['defaultRegisterRole']));
                }
            } else {
                throw new DoctrineAuthException('No registration role found in config');
            }
        }
        
    }

    /**
     * Add ACL resources
     * @param array $config
     * @throws DoctrineAuthException
     */
    private function addResources(Array $config)
    {
        if (array_key_exists('resources', $config['doctrineAuthAcl'])) {
            foreach ($config['doctrineAuthAcl']['resources'] as $resource) {
                $this->addResource(new Rescource($resource['module']));
                if (array_key_exists('controllers', $resource) && is_array($resource['controllers']) && !empty($resource['controllers'])) {
                    foreach ($resource['controllers'] as $controller) {
                        $this->addResource(new Rescource($controller), $resource['module']);
                    }
                }
            }
        } else {
            throw new DoctrineAuthException('No resources found in config');
        }
    }

    /**
     * Add ACL permissions
     * @param array $config
     * @throws DoctrineAuthException
     */
    private function addPermissions(Array $config)
    {
        if (array_key_exists('permissions', $config['doctrineAuthAcl'])) {
            foreach ($config['doctrineAuthAcl']['permissions'] as $permission) {
                $this->setRule(self::OP_ADD, $permission['type'], $permission['role'], $permission['resource'], $permission['actions']);
            }
        } else {
            throw new DoctrineAuthException('No permissions found in config');
        }
    }
    
    /**
     * Get registration role
     * @return Role|NULL
     */
    public function getDefaultRegistrationRole()
    {
        return $this->defaultRegistrationRole;
    }

    /**
     * Get stored redirect
     * @param string $role
     * @return array
     */
    public function getRedirect($role): Array
    {
        return $this->getRole($role)->getRoute();
    }

}
