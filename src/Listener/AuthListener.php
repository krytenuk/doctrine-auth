<?php

namespace FwsDoctrineAuth\Listener;

use Laminas\Authentication\AuthenticationService;
use FwsDoctrineAuth\Controller\IndexController;
use FwsDoctrineAuth\Model\Acl;
use Exception;
use Laminas\Http\Response;
use FwsDoctrineAuth\Entity\BaseUsers;
use Laminas\Mvc\MvcEvent;
use Laminas\View\Model\JsonModel;

/**
 * Description of AuthListener
 *
 * @author Garry Childs <info@freedomwebservices.net>
 */
class AuthListener
{

    public function checkUser(MvcEvent $event)
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
                throw new Exception(sprintf('ACL Resource "%s" not defined', $controller));
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
                $response->setStatusCode(Response::STATUS_CODE_200);
                $viewModel = new JsonModel(['redirect' => $event->getRouter()->assemble(['action' => 'login'], ['name' => 'doctrine-auth/default', 'force_canonical' => TRUE])]);
                $event->setViewModel($viewModel);
                $event->stopPropagation(true);
                return $viewModel;
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
                $response->getHeaders()->addHeaderLine('Location', $url);
                $response->setStatusCode(Response::STATUS_CODE_302);
                $response->sendHeaders();
                $event->stopPropagation(true);
                return $response;
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
