<?php

namespace FwsDoctrineAuth\Controller\Plugin\Service;

use FwsDoctrineAuth\Controller\Plugin\ValidateHash;
use FwsDoctrineAuth\Model\AuthContainerStorage;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class ValidateHashFactory implements FactoryInterface
{

    /**
     * @param ContainerInterface $container
     * @param $requestedName
     * @param array|null $options
     * @return ValidateHash
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): ValidateHash
    {
        return new ValidateHash($container->get(AuthContainerStorage::class));
    }
}