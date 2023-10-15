<?php

namespace FwsDoctrineAuth\Model\Service;

use Doctrine\ORM\EntityManager;
use FwsDoctrineAuth\Exception\DoctrineAuthException;
use FwsDoctrineAuth\Model\Acl;
use FwsDoctrineAuth\Model\LoginModel;
use Laminas\Authentication\AuthenticationService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\Session\SessionManager;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Description of LoginModelFactory
 *
 * @author Garry Childs (Freedom Web Services)
 */
class LoginModelFactory implements FactoryInterface
{

    /**
     * Create login model class
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return LoginModel
     * @throws DoctrineAuthException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): LoginModel
    {
        $config = $container->get('config');
        $loginForm = $config['doctrineAuth']['loginForm'] ?? null;
        if (!$loginForm) {
            throw new DoctrineAuthException('"loginForm" not found in config');
        }
        $formElementManager = $container->get('FormElementManager');
        if (!($formElementManager->has($loginForm) && class_exists($loginForm))) {
            throw new DoctrineAuthException(sprintf('Login form "%s" not found', $loginForm));
        }

        return new LoginModel(
                $formElementManager->get($loginForm),
                $container->get(AuthenticationService::class),
                $container->get(EntityManager::class),
                $container->get('authContainerStorage'),
                $container->get(SessionManager::class),
                $container->get(Acl::class),
                $config
        );
    }

}
