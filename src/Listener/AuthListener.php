<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\Listener;

use FwsDoctrineAuth\Controller\LoginController;
use FwsDoctrineAuth\Entity\BaseUser;
use FwsDoctrineAuth\Exception\DoctrineAuthException;
use FwsDoctrineAuth\Model\Acl;
use Laminas\Authentication\AuthenticationService;
use Laminas\Http\Response;
use Laminas\Mvc\MvcEvent;
use Laminas\Stdlib\ResponseInterface;
use Laminas\View\Model\JsonModel;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

use function in_array;
use function sprintf;

/**
 * Description of AuthListener
 */
class AuthListener
{
    /**
     * @return Response|JsonModel|void
     * @throws ContainerExceptionInterface
     * @throws DoctrineAuthException
     * @throws NotFoundExceptionInterface
     */
    public function checkUser(MvcEvent $event)
    {
        $application    = $event->getApplication();
        $routeMatch     = $event->getRouteMatch();
        $serviceManager = $application->getServiceManager();
        /** @var AuthenticationService $auth */
        $auth = $serviceManager->get(AuthenticationService::class);

        /** @var Acl $acl */
        $acl = $serviceManager->get('acl');

        /* Get user role */
        $role = $acl->getDefaultRole();
        if ($auth->hasIdentity()) {
            /** @var BaseUser $user */
            $user = $auth->getIdentity();
            if ($user instanceof BaseUser) {
                $role = $user->getUserRole()->getRole();
            }
        }

        /* Get controller and action from route */
        $controller = $routeMatch->getParam('controller');
        $action     = $routeMatch->getParam('action');

        /* Resource not found in ACL (defined in config) */
        if (! $acl->hasResource($controller)) {
            $config = $serviceManager->get('config');
            if (isset($config['controllers']['aliases'])) {
                $controller = $this->getControllerAlias($controller, $acl, $config['controllers']['aliases']);
            } else {
                throw new DoctrineAuthException(sprintf('ACL Resource "%s" not defined', $controller));
            }
        }

        /** User allowed to access resource */
        if ($acl->isAllowed($role, $controller, $action)) {
            return;
        }

        $request  = $event->getRequest();
        $response = $event->getResponse();
        /** ajax request */
        if ($request->isXmlHttpRequest()) {
            $response->setStatusCode(Response::STATUS_CODE_200);
            $viewModel = new JsonModel(['redirect' => $event->getRouter()->assemble(['action' => 'login'], ['name' => 'doctrine-auth/default', 'force_canonical' => true])]);
            $event->setViewModel($viewModel);
            $event->stopPropagation();
            return $viewModel;
        } else {
            /** On login page */
            if ($controller == LoginController::class && $action == 'login') {
                /* Redirect to log out */
                return $this->redirect($event, $response, $event->getRouter()->assemble(['action' => 'logout'], ['name' => 'doctrine-auth/default']));
            }

            /** User trying to access restricted page */
            if ($controller !== LoginController::class) {
                /** Store page user is trying to access in session */
                $container           = $serviceManager->get('authContainerStorage');
                $container->redirect = [
                    'url'        => $event->getRouter()->getRequestUri()->toString(),
                    'controller' => $controller,
                    'action'     => $action,
                ];
            }
            /* Redirect to login */
            return $this->redirect($event, $response, $event->getRouter()->assemble(['action' => 'login'], ['name' => 'doctrine-auth/default']));
        }
    }

    /**
     * Redirect with 302 http status code
     *
     * @param Response $response
     */
    private function redirect(MvcEvent $event, ResponseInterface $response, string $url): Response
    {
        $response->getHeaders()->addHeaderLine('Location', $url);
        $response->setStatusCode(Response::STATUS_CODE_302);
        $response->sendHeaders();
        $event->stopPropagation();
        return $response;
    }

    /**
     * Get controller alias
     *
     * @param array $aliases
     * @throws DoctrineAuthException
     */
    public function getControllerAlias(string $controller, Acl $acl, array $aliases): string
    {
        if (in_array($controller, $aliases)) {
            if ($acl->hasResource($aliases[$controller])) {
                return $aliases[$controller];
            }
        }
        throw new DoctrineAuthException('ACL resource or controller alias "' . $controller . '" not defined');
    }
}
