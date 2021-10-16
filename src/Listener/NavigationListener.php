<?php

namespace FwsDoctrineAuth\Listener;

use Laminas\Mvc\MvcEvent;
use Laminas\Authentication\AuthenticationService;
use FwsDoctrineAuth\Entity\BaseUsers;

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
     */
    public function addAcl(MvcEvent $event)
    {
        $serviceManager = $event->getApplication()->getServiceManager();

        $config = $serviceManager->get('config');

        if (isset($config['doctrineAuthAcl']['injectAclIntoNavigation']) && $config['doctrineAuthAcl']['injectAclIntoNavigation'] === TRUE) {
            /* @var \Laminas\View\Helper\Navigation $plugin */
            $plugin = $serviceManager->get('ViewHelperManager')->get('navigation');

            $acl = $serviceManager->get('acl');

            $auth = $serviceManager->get(AuthenticationService::class);

            $role = $acl->getDefultRole();;
            if ($auth->hasIdentity()) {
                $user = $auth->getIdentity();
                if ($user instanceof BaseUsers) {
                    $role = $user->getUserRole()->getRole();
                }
            }
            $plugin->setAcl($acl);
            $plugin->setRole($role);
        }
    }

}
