<?php

namespace FwsDoctrineAuth\Controller\Plugin\Service;

use FwsDoctrineAuth\Controller\Plugin\GetRedirect;
use FwsDoctrineAuth\Model\Acl;
use FwsDoctrineAuth\Model\AuthContainerStorage;
use FwsDoctrineAuth\Model\LoginModel;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class GetRedirectFactory implements FactoryInterface
{

    /**
     * @inheritDoc
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): GetRedirect
    {
        return new GetRedirect(
            $container->get(Acl::class),
            $container->get(AuthContainerStorage::class)
        );
    }
}