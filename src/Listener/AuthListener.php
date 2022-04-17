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
        /* @var $auth AuthenticationService */
        $auth = $serviceManager->get(AuthenticationService::class);

        /* @var $acl \FwsDoctrineAuth\Model\Acl */
        $acl = $serviceManager->get('acl');

        /* Get user role */
        $role = $acl->getDefultRole();
        if ($auth->hasIdentity() === true) {
            /* @var $user BaseUsers */
            $user = $auth->getIdentity();
            if ($user instanceof BaseUsers === true) {
                $role = $user->getUserRole()->getRole();
            }
        }

        /* Get controller and action from route */
        $controller = $routeMatch->getParam('controller');
        $action = $routeMatch->getParam('action');

        /* Resource not found in ACL (defined in config) */
        if ($acl->hasResource($controller) === false) {
            $config = $serviceManager->get('config');
            if (isset($config['controllers']['aliases']) === true) {
                $controller = $this->getControllerAlias($controller, $acl, $config['controllers']['aliases']);
            } else {
                throw new Exception(sprintf('ACL Resource "%s" not defined', $controller));
            }
        }

        /* User allowed to access resource */
        if ($acl->isAllowed($role, $controller, $action) === true) {
            return;
        }

        /*
         * User NOT allowed to access resource
         */
        $request = $event->getRequest();
        $response = $event->getResponse();
        /* ajax request */
        if ($request->isXmlHttpRequest()) {
            $response->setStatusCode(Response::STATUS_CODE_200);
            $viewModel = new JsonModel(['redirect' => $event->getRouter()->assemble(['action' => 'login'], ['name' => 'doctrine-auth/default', 'force_canonical' => true])]);
            $event->setViewModel($viewModel);
            $event->stopPropagation(true);
            return $viewModel;
        } else {
            /* On login page */
            if ($controller == IndexController::class && $action == 'login') {
                /* Redirect to logout */
                return $this->redirect($event, $response, $event->getRouter()->assemble(['action' => 'logout'], ['name' => 'doctrine-auth/default']));
            }

            /* User trying to access restricted page */
            if ($controller !== IndexController::class) {
                /* Store page user is trying to access */
                $container = $serviceManager->get('authContainer');
                $container->redirect = [
                    'url' => $event->getRouter()->getRequestUri()->toString(),
                    'controller' => $controller,
                    'action' => $action,
                ];
            }
            /* Redirect to login */
            return $this->redirect($event, $response, $event->getRouter()->assemble(['action' => 'login'], ['name' => 'doctrine-auth/default']));
        }
    }

    /**
     * Redirect with 302 http status code
     * @param Response $response
     * @param string $url
     * @return Response
     */
    private function redirect(MvcEvent $event, Response $response, string $url): Response
    {
        $response->getHeaders()->addHeaderLine('Location', $url);
        $response->setStatusCode(Response::STATUS_CODE_302);
        $response->sendHeaders();
        $event->stopPropagation(true);
        return $response;
    }

    /**
     * Get controller alias
     * @param string $controller
     * @param Acl $acl
     * @param array $aliases
     * @return boolean|string
     * @throws \Exception
     */
    public function getControllerAlias($controller, Acl $acl, array $aliases)
    {
        if (in_array($controller, $aliases)) {
            if ($acl->hasResource($aliases[$controller])) {
                return $aliases[$controller];
            }
        }
        throw new Exception('ACL resource or controller alias "' . $controller . '" not defined');
    }

}
