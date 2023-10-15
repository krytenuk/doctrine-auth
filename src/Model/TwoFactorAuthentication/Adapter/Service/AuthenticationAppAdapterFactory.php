<?php

namespace FwsDoctrineAuth\Model\TwoFactorAuthentication\Adapter\Service;

use FwsDoctrineAuth\Model\TwoFactorAuthentication\Adapter\AuthenticationAppAdapter;
use Psr\Container\ContainerInterface;

class AuthenticationAppAdapterFactory implements \Laminas\ServiceManager\Factory\FactoryInterface
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