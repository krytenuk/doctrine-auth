<?php

namespace FwsDoctrineAuth\Controller\Service;

use FwsDoctrineAuth\Controller\ManageTwoFactorAuthenticationController;
use FwsDoctrineAuth\Model\TwoFactorAuthentication\ManageTwoFactorAuthenticationModel;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class ManageTwoFactorAuthenticationControllerFactory implements FactoryInterface
{

    /**
     * @inheritDoc
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): ManageTwoFactorAuthenticationController
    {
        return new ManageTwoFactorAuthenticationController(
            $container->get(ManageTwoFactorAuthenticationModel::class)
        );
    }
}