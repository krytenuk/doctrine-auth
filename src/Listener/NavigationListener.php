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

        /* Don't inject ACL into navigation view helper (set in config) */
        if (isset($config['doctrineAuthAcl']['injectAclIntoNavigation']) === false || $config['doctrineAuthAcl']['injectAclIntoNavigation'] === false) {
            return;
        }

        /* @var \Laminas\View\Helper\Navigation $plugin */
        $plugin = $serviceManager->get('ViewHelperManager')->get('navigation');

        /* @var $acl \FwsDoctrineAuth\Model\Acl */
        $acl = $serviceManager->get('acl');

        /* @var $auth AuthenticationService */
        $auth = $serviceManager->get(AuthenticationService::class);

        $role = $acl->getDefultRole();

        if ($auth->hasIdentity() === true) {
            $user = $auth->getIdentity();
            if ($user instanceof BaseUsers === true) {
                $role = $user->getUserRole()->getRole();
            }
        }
        $plugin->setAcl($acl);
        $plugin->setRole($role);
    }

}
