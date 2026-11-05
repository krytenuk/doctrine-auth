<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\Service;

use FwsDoctrineAuth\TwoFactor\TwoFactorMethods;
use Psr\Container\ContainerInterface;

final class TwoFactorMethodsFactory
{
    public function __invoke(ContainerInterface $container): TwoFactorMethods
    {
        return new TwoFactorMethods(
            $container,
            $container->get('config')['doctrine-auth']['two_factor']['methods'] ?? []
        );
    }
}
