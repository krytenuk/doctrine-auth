<?php

namespace FwsDoctrineAuth\Controller\Service;

use FwsDoctrineAuth\Controller\TwoFactorAuthenticationController;
use FwsDoctrineAuth\Model\TwoFactorAuthentication\TwoFactorAuthenticationModel;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class TwoFactorAuthenticationControllerFactory implements FactoryInterface
{

    /**
     * @inheritDoc
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): TwoFactorAuthenticationController
    {
        return new TwoFactorAuthenticationController($container->get(TwoFactorAuthenticationModel::class));
    }
}