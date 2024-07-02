<?php

namespace FwsDoctrineAuth\Model\TwoFactorAuthentication\Adapter\Service;

use FwsDoctrineAuth\Model\TwoFactorAuthentication\Adapter\AuthenticationAppAdapter;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class AuthenticationAppAdapterFactory implements FactoryInterface
{

    /**
     * @inheritDoc
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): AuthenticationAppAdapter
    {
        return new AuthenticationAppAdapter(
            $container->get('authContainerStorage'),
            $container->get('config')
        );
    }
}