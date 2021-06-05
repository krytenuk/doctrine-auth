<?php

namespace FwsDoctrineAuth\Listener;

use Laminas\EventManager\EventInterface;
use Laminas\Authentication\AuthenticationService;
use FwsDoctrineAuth\Controller\IndexController;
use FwsDoctrineAuth\Model\Acl;
use Exception;
use Laminas\Json\Json;
use Laminas\Http\Response;
use FwsDoctrineAuth\Entity\BaseUsers;

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
            if ($user instanceof BaseUsers) {
                $role = $user->getUserRole()->getRole();
            }
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
            $request = $event->getRequest();
            $response = $event->getResponse();
            /**
             * ajax request
             */
            if ($request->isXmlHttpRequest()) {
                $event->stopPropagation(true);
                $response->setContent(Json::encode(array('redirect' => $event->getRouter()->assemble(array('action' => 'login'), array('name' => 'doctrine-auth/default', 'force_canonical' => TRUE)))));
                $response->setStatusCode(Response::STATUS_CODE_200);
                $response->getHeaders()->addHeaderLine('Content-Type', 'application/json');
                $response->send();
                exit;
            } else {
                if ($controller == IndexController::class && $action == 'login') {
                    $url = $event->getRouter()->assemble(array('action' => 'logout'), array('name' => 'doctrine-auth/default')); // url to logout
                } else {
                    /**
                     * On login page?
                     */
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
                $response->setStatusCode(403);
                $response->sendHeaders();
                exit;
            }
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
