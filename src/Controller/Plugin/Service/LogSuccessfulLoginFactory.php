<?php

namespace FwsDoctrineAuth\Controller\Plugin\Service;

use Doctrine\ORM\EntityManager;
use FwsDoctrineAuth\Controller\Plugin\LogSuccessfulLogin;
use Laminas\Authentication\AuthenticationService;
use Psr\Container\ContainerInterface;

class LogSuccessfulLoginFactory implements \Laminas\ServiceManager\Factory\FactoryInterface
{

    /**
     * @inheritDoc
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): LogSuccessfulLogin
    {
        return new LogSuccessfulLogin(
            $container->get(EntityManager::class),
            $container->get(AuthenticationService::class)
        );
    }
}