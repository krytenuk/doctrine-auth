<?php

namespace FwsDoctrineAuth\Model\Service;

use FwsDoctrineAuth\Model\AuthContainerStorage;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\Session\Container;
use Psr\Container\ContainerInterface;

class AuthContainerStorageFactory implements FactoryInterface
{

    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return Container
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): Container
    {
        return new AuthContainerStorage('auth');
    }

}
