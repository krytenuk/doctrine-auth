<?php

namespace FwsDoctrineAuth\Model\TwoFactorAuthentication\Adapter\Service;

use FwsDoctrineAuth\Model\TwoFactorAuthentication\Adapter\EmailAdapter;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\View\Renderer\PhpRenderer;
use Psr\Container\ContainerInterface;

class EmailAdapterFactory implements FactoryInterface
{

    /**
     * @inheritDoc
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): EmailAdapter
    {
        return new EmailAdapter(
            $container->get(PhpRenderer::class)
        );
    }
}