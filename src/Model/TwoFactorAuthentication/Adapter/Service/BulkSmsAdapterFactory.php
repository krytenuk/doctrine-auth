<?php

namespace FwsDoctrineAuth\Model\TwoFactorAuthentication\Adapter\Service;

use FwsDoctrineAuth\Model\TwoFactorAuthentication\Adapter\BulkSmsAdapter;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\View\Renderer\PhpRenderer;
use Psr\Container\ContainerInterface;

class BulkSmsAdapterFactory implements FactoryInterface
{

    /**
     * @inheritDoc
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): BulkSmsAdapter
    {
        return new BulkSmsAdapter(
            $container->get(PhpRenderer::class)
        );
    }
}