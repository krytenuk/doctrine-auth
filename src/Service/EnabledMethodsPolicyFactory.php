<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\Service;

use FwsDoctrineAuth\TwoFactor\EnabledMethodsPolicy;
use FwsDoctrineAuth\TwoFactor\TwoFactorMethods;
use Psr\Container\ContainerInterface;

final class EnabledMethodsPolicyFactory
{
    public function __invoke(ContainerInterface $container): EnabledMethodsPolicy
    {
        return new EnabledMethodsPolicy($container->get(TwoFactorMethods::class));
    }
}
