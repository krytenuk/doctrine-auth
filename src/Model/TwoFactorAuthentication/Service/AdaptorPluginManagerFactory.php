<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\Model\TwoFactorAuthentication\Service;

use FwsDoctrineAuth\Exception\DoctrineAuthException;
use FwsDoctrineAuth\Model\TwoFactorAuthentication\AdaptorPluginManager;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

use function array_key_exists;

class AdaptorPluginManagerFactory implements FactoryInterface
{
    /**
     * @inheritDoc
     * @throws DoctrineAuthException
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): AdaptorPluginManager
    {
        $config   = $container->get('config');
        $adaptors = $config['doctrineAuth']['allowedTwoFactorAuthenticationAdaptors'] ?? null;
        if (! $adaptors) {
            throw new DoctrineAuthException('allowedTwoFactorAuthenticationAdaptors config key not set');
        }

        $serviceConfig  = $config['service_manager']['factories'] ?? [];
        $adaptorsConfig = [
            'factories' => [],
            'aliases'   => [],
        ];

        foreach ($adaptors as $adaptor) {
            if (array_key_exists($adaptor, $serviceConfig)) {
                $adaptorsConfig['factories'][$adaptor]          = $serviceConfig[$adaptor];
                $adaptorsConfig['aliases'][$adaptor::getName()] = $adaptor;
            }
        }
        return new AdaptorPluginManager($container, $adaptorsConfig);
    }
}
