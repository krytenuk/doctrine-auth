<?php

namespace FwsDoctrineAuth\Listener;

use Zend\EventManager\EventInterface;
use Zend\Authentication\AuthenticationService;
use FwsDoctrineAuth\Controller\IndexController;
use FwsDoctrineAuth\Model\Acl;
use Exception;

/**
 * Description of AuthListener
 *
 * @author Garry Childs <info@freedomwebservices.net>
 */
class AuthListener
{

    public function checkUser(EventInterface $event)
    {
        $application = $event->getApplication();
        $routeMatch = $event->getRouteMatch();
        $serviceManager = $application->getServiceManager();
        $auth = $serviceManager->get(AuthenticationService::class);

        /* @var $acl \FwsDoctrineAuth\Model\Acl */
        $acl = $serviceManager->get('acl');

        /**
         * Get user role
         */
        $role = $acl->getDefultRole();
        if ($auth->hasIdentity()) {
            $user = $auth->getIdentity();
            $role = $user->getUserRole()->getRole();
        }

        $controller = $routeMatch->getParam('controller');
        $action = $routeMatch->getParam('action');
        if (!$acl->hasResource($controller)) {
            $config = $serviceManager->get('config');
            if (isset($config['controllers']['aliases'])) {
                $controller = $this->getControllerAlias($controller, $acl, $config['controllers']['aliases']);
            } else {
                throw new Exception('ACL Resource "' . $controller . '" not defined');
            }
        }

        /**
         * User NOT allowed to access resource
         */
        if (!$acl->isAllowed($role, $controller, $action)) {
            /**
             * On login page?
             */
            if ($controller == IndexController::class && $action == 'login') {
                $url = $event->getRouter()->assemble(array('action' => 'logout'), array('name' => 'doctrine-auth/default')); // url to logout
            } else {
                $url = $event->getRouter()->assemble(array('action' => 'login'), array('name' => 'doctrine-auth/default')); // url to login
                /**
                 * User not logged in
                 */
                if ($role == $acl->getDefultRole()) {
                    /**
                     * User trying to access restricted page?
                     */
                    if ($controller != 'FwsDoctrineAuth\Controller\IndexController') {
                        /**
                         * Page user is trying to access
                         */
                        $container = $serviceManager->get('authContainer');
                        $container->redirect = array(
                            'url' => $event->getRouter()->getRequestUri()->toString(),
                            'controller' => $controller,
                            'action' => $action,
                        );
                    }
                }
            }
            /**
             * Redirect with 302 http status code
             */
            $response = $event->getResponse();
            $response->getHeaders()->addHeaderLine('Location', $url);
            $response->setStatusCode(302);
            $response->sendHeaders();
            exit;
        }
    }
    
    /**
     * 
     * @param string $controller
     * @param Acl $acl
     * @param array $aliases
     * @return boolean|string
     * @throws \Exception
     */
    public function getControllerAlias($controller, Acl $acl, Array $aliases)
    {
        if (array_key_exists($controller, $aliases)) {
            if ($acl->hasResource($aliases[$controller])) {
                return $aliases[$controller];
            }
        }
        throw new Exception('ACL resource or controller alias "' . $controller . '" not defined');
    }

}
