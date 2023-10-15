<?php

namespace FwsDoctrineAuth\Controller\Service;

use FwsDoctrineAuth\Controller\LoginController;
use FwsDoctrineAuth\Model;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * IndexControllerFactory
 *
 * @author Garry Childs (Freedom Web Services)
 */
class LoginControllerFactory implements FactoryInterface
{

    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return LoginController
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): LoginController
    {
        return new LoginController(
                $container->get(Model\LoginModel::class), 
                $container->get(Model\RegisterModel::class),
                $container->get(Model\ForgotPasswordModel::class),
                $container->get(Model\TwoFactorAuthentication\ManageTwoFactorAuthenticationModel::class),
                $container->get(Model\TwoFactorAuthentication\TwoFactorAuthenticationModel::class)
        );
    }

}
