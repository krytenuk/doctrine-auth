<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\Model;

use FwsDoctrineAuth\Exception\DoctrineAuthException;
use FwsDoctrineAuth\Permissions\DoctrineAuthRole as Role;
use Laminas\Permissions\Acl\Acl as LaminasAcl;
use Laminas\Permissions\Acl\Resource\GenericResource as Resource;
use Laminas\Permissions\Acl\Role\RoleInterface;

use function array_key_exists;
use function is_array;
use function sprintf;

/**
 * Build Access Control List from config array
 */
class Acl extends LaminasAcl
{
    private ?string $defaultRole                    = null;
    private ?RoleInterface $defaultRegistrationRole = null;

    /**
     * Setup access control list
     *
     * @param array $config
     * @throws DoctrineAuthException
     */
    public function __construct(array $config)
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
     *
     * @param array $config
     * @throws DoctrineAuthException
     */
    private function addRoles(array $config): void
    {
        /* Check roles exist in config */
        if (! array_key_exists('roles', $config['doctrineAuthAcl'])) {
            throw new DoctrineAuthException('No roles found in config');
        }

        $this->defaultRole = null;
        /* Loop through roles in config */
        foreach ($config['doctrineAuthAcl']['roles'] as $role) {
            $redirect = [];
            /* Role has redirect set */
            if (array_key_exists('redirect', $role)) {
                $redirect = $role['redirect'];
                /* No redirect set for role which is not guest */
            } elseif (array_key_exists('id', $role) && $role['id'] !== 'guest') {
                throw new DoctrineAuthException(sprintf("Role's default redirect not defined in config for \"%s\"", $role['id']));
            }
            /** Add user role to ACL */
            $this->addRole(new Role($role['id'], $redirect), $role['parents']);
            if (array_key_exists('default', $role) && $role['default'] === true) {
                $this->defaultRole = $role['id'];
            }
        }

        /* No default role set in config */
        if ($this->defaultRole === null) {
            throw new DoctrineAuthException('Default role not defined');
        }

        $this->setDefaultRegisterRole($config);
    }

    /**
     * Set default registration role
     *
     * @param array $config
     * @throws DoctrineAuthException
     */
    private function setDefaultRegisterRole(array $config): void
    {
        /* Allow registration not set in config */
        if (isset($config['doctrineAuth']['allowRegistration']) && ! $config['doctrineAuth']['allowRegistration']) {
            return;
        }

        /* Default registration role not defined in config */
        if (! array_key_exists('defaultRegisterRole', $config['doctrineAuthAcl'])) {
            throw new DoctrineAuthException('No registration role found in config');
        }

        /* Store default register role if set in config */
        if ($this->hasRole($config['doctrineAuthAcl']['defaultRegisterRole'])) {
            $this->defaultRegistrationRole = $this->getRole($config['doctrineAuthAcl']['defaultRegisterRole']);
        } else {
            throw new DoctrineAuthException(sprintf('Registration role "%s" not found', $config['doctrineAuthAcl']['defaultRegisterRole']));
        }
    }

    /**
     * Add ACL resources
     *
     * @param array $config
     * @throws DoctrineAuthException
     */
    private function addResources(array $config): void
    {
        /* No resources found in config */
        if (! array_key_exists('resources', $config['doctrineAuthAcl'])) {
            throw new DoctrineAuthException('No resources found in config');
        }

        /* Add resources to ACL */
        foreach ($config['doctrineAuthAcl']['resources'] as $resource) {
            $this->addModuleResource($resource);
        }
    }

    /**
     * Add module resource to ACL
     *
     * @param array $resource
     */
    private function addModuleResource(array $resource): void
    {
        /* Add module resource */
        $this->addResource(new Resource($resource['module']));
        /* Module resource has controllers */
        if (array_key_exists('controllers', $resource) && is_array($resource['controllers']) && ! empty($resource['controllers'])) {
            /* Add module controllers as children of module resource */
            foreach ($resource['controllers'] as $controller) {
                $this->addResource(new Resource($controller), $resource['module']);
            }
        }
    }

    /**
     * Add ACL permissions
     *
     * @param array $config
     * @throws DoctrineAuthException
     */
    private function addPermissions(array $config): void
    {
        /* No permissions set in config */
        if (! array_key_exists('permissions', $config['doctrineAuthAcl'])) {
            throw new DoctrineAuthException('No permissions found in config');
        }

        /* Add permissions to ACL */
        foreach ($config['doctrineAuthAcl']['permissions'] as $permission) {
            $this->setRule(self::OP_ADD, $permission['type'], $permission['role'], $permission['resource'], $permission['actions']);
        }
    }

    /**
     * Get the default role
     * This is the role of a user not logged in
     */
    public function getDefaultRole(): string
    {
        return $this->defaultRole;
    }

    /**
     * Get registration role
     */
    public function getDefaultRegistrationRole(): ?RoleInterface
    {
        return $this->defaultRegistrationRole;
    }

    /**
     * Get stored redirect for given role
     *
     * @return array
     */
    public function getRedirect(string $role): array
    {
        return $this->getRole($role)->getRoute();
    }
}
