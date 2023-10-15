<?php

namespace FwsDoctrineAuth\Controller\Plugin\Service;

use Doctrine\ORM\EntityManager;
use FwsDoctrineAuth\Controller\Plugin\LogFailedAttempt;
use Psr\Container\ContainerInterface;

class LogFailedAttemptFactory implements \Laminas\ServiceManager\Factory\FactoryInterface
{

    /**
     * @inheritDoc
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        return new LogFailedAttempt(
            $container->get(EntityManager::class),
            $container->get('request')->getServer()
        );
    }
}