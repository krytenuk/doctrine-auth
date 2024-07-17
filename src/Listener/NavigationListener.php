<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\Listener;

use FwsDoctrineAuth\Entity\BaseUser;
use FwsDoctrineAuth\Model\Acl;
use Laminas\Authentication\AuthenticationService;
use Laminas\Mvc\MvcEvent;
use Laminas\View\Helper\Navigation;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * NavigationListener
 */
class NavigationListener
{
    /**
     * Inject ACL & user role into navigation view helper
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function addAcl(MvcEvent $event): void
    {
        $serviceManager = $event->getApplication()->getServiceManager();

        $config = $serviceManager->get('config');

        /* Don't inject ACL into navigation view helper (set in config) */
        if (! (isset($config['doctrineAuthAcl']['injectAclIntoNavigation']) && $config['doctrineAuthAcl']['injectAclIntoNavigation'])) {
            return;
        }

        /** @var Navigation $plugin */
        $plugin = $serviceManager->get('ViewHelperManager')->get('navigation');

        /** @var Acl $acl */
        $acl = $serviceManager->get('acl');

        /** @var AuthenticationService $auth */
        $auth = $serviceManager->get(AuthenticationService::class);

        $role = $acl->getDefaultRole();

        if ($auth->hasIdentity()) {
            $user = $auth->getIdentity();
            if ($user instanceof BaseUser) {
                $role = $user->getUserRole()->getRole();
            }
        }
        $plugin->setAcl($acl);
        $plugin->setRole($role);
    }
}
