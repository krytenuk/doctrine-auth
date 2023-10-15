<?php

namespace FwsDoctrineAuth\Listener;

use FwsDoctrineAuth\Model\Acl;
use Laminas\Mvc\MvcEvent;
use Laminas\Authentication\AuthenticationService;
use FwsDoctrineAuth\Entity\BaseUser;
use Laminas\View\Helper\Navigation;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * NavigationListener
 *
 * @author Garry Childs <info@freedomwebservices.net>
 */
class NavigationListener
{

    /**
     * Inject ACL & user role into navigation view helper
     * @param MvcEvent $event
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function addAcl(MvcEvent $event): void
    {
        $serviceManager = $event->getApplication()->getServiceManager();

        $config = $serviceManager->get('config');

        /* Don't inject ACL into navigation view helper (set in config) */
        if (!(isset($config['doctrineAuthAcl']['injectAclIntoNavigation']) && $config['doctrineAuthAcl']['injectAclIntoNavigation'])) {
            return;
        }

        /* @var Navigation $plugin */
        $plugin = $serviceManager->get('ViewHelperManager')->get('navigation');

        /* @var $acl Acl */
        $acl = $serviceManager->get('acl');

        /* @var $auth AuthenticationService */
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
