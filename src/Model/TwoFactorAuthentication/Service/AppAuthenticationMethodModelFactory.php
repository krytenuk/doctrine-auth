<?php

namespace FwsDoctrineAuth\Model\TwoFactorAuthentication\Service;

use FwsDoctrineAuth\Exception\DoctrineAuthException;
use FwsDoctrineAuth\Form\DefaultForm;
use FwsDoctrineAuth\Form\SelectTwoFactorAuthMethodForm;
use FwsDoctrineAuth\Form\Service\DoctrineAuthFormFactory;
use FwsDoctrineAuth\Model\TwoFactorAuthentication\AdaptorPluginManager;
use FwsDoctrineAuth\Model\TwoFactorAuthentication\AppAuthenticationMethodModel;
use FwsDoctrineAuth\Model\TwoFactorAuthentication\ManageTwoFactorAuthenticationModel;
use FwsDoctrineAuth\Model\TwoFactorAuthentication\TwoFactorAuthenticationModel;
use Laminas\Authentication\AuthenticationService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class AppAuthenticationMethodModelFactory implements FactoryInterface
{

    /**
     * @param ContainerInterface $container
     * @param $requestedName
     * @param array|null $options
     * @return AppAuthenticationMethodModel
     * @throws ContainerExceptionInterface
     * @throws DoctrineAuthException
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): AppAuthenticationMethodModel
    {


        return new AppAuthenticationMethodModel(
            $container->get(ManageTwoFactorAuthenticationModel::class),
            $container->get(TwoFactorAuthenticationModel::class),
            $container->get('config')
        );
    }
}