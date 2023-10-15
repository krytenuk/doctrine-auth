<?php

namespace FwsDoctrineAuth\Controller\Service;

use FwsDoctrineAuth\Controller\AppAuthenticationController;
use FwsDoctrineAuth\Model\TwoFactorAuthentication\AppAuthenticationMethodModel;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class AppAuthenticationControllerFactory implements FactoryInterface
{

    /**
     * @inheritDoc
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        return new AppAuthenticationController(
            $container->get(AppAuthenticationMethodModel::class)
        );
    }
}