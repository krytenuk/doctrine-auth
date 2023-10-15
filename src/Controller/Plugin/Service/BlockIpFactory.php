<?php

namespace FwsDoctrineAuth\Controller\Plugin\Service;

use Doctrine\ORM\EntityManager;
use FwsDoctrineAuth\Controller\Plugin\BlockIP;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class BlockIpFactory implements FactoryInterface
{

    /**
     * @inheritDoc
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): BlockIP
    {
        return new BlockIP(
            $container->get(EntityManager::class),
            $container->get('request')->getServer(),
            $container->get('config')
        );
    }
}