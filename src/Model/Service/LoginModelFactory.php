<?php

namespace FwsDoctrineAuth\Model\Service;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use FwsDoctrineAuth\Model\LoginModel;
use FwsDoctrineAuth\Model\TwoFactorAuthModel;
use Laminas\Authentication\AuthenticationService;
use FwsDoctrineAuth\Exception\DoctrineAuthException;
use Doctrine\ORM\EntityManager;
use Laminas\Session\SessionManager;
use FwsDoctrineAuth\Model\Acl;

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
     * @param array $options
     * @return LoginModel
     */
    public function __invoke(ContainerInterface $container, $requestedName, Array $options = null)
    {
        $config = $container->get('config');

        if (isset($config['doctrineAuth']['loginForm']) === false) {
            throw new DoctrineAuthException('"loginForm" not found in config');
        }
        if (class_exists($config['doctrineAuth']['loginForm']) === false) {
            throw new DoctrineAuthException(sprintf('Login form "%s" not found', $config['doctrineAuth']['loginForm']));
        }

        return new LoginModel(
                $container->get(TwoFactorAuthModel::class),
                $container->get('FormElementManager')->get($config['doctrineAuth']['loginForm']),
                $container->get(AuthenticationService::class),
                $container->get(EntityManager::class),
                $container->get('authContainer'),
                $container->get(SessionManager::class),
                $container->get(Acl::class),
                $container->get('request')->getServer(),
                $config
        );
    }

}
