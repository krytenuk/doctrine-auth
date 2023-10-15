<?php

namespace FwsDoctrineAuth\Controller\Plugin\Service;

use Doctrine\ORM\EntityManager;
use FwsDoctrineAuth\Controller\Plugin\IsIpBlocked;
use Psr\Container\ContainerInterface;

class IsIpBlockedFactory implements \Laminas\ServiceManager\Factory\FactoryInterface
{

    /**
     * @inheritDoc
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): IsIpBlocked
    {
        return new IsIpBlocked(
            $container->get(EntityManager::class),
            $container->get('request')->getServer(),
            $container->get('config')
        );
    }
}