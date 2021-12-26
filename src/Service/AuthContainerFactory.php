<?php

namespace FwsDoctrineAuth\Service;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Laminas\Session\Container;

class AuthContainerFactory implements FactoryInterface
{

    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array $options
     * @return Container
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        return new Container('auth');
    }

}
