<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\Service;

use FwsDoctrineAuth\Clock\SystemClock;
use FwsDoctrineAuth\Event\AuthEventDispatcher;
use FwsDoctrineAuth\Mezzio\SessionLogin;
use FwsDoctrineAuth\Mezzio\TwoFactorLogin;
use FwsDoctrineAuth\Throttle\LoginThrottleInterface;
use FwsDoctrineAuth\TwoFactor\TwoFactorMethods;
use FwsDoctrineAuth\User\UserProviderInterface;
use Psr\Container\ContainerInterface;

final class TwoFactorLoginFactory
{
    public function __invoke(ContainerInterface $container): TwoFactorLogin
    {
        return new TwoFactorLogin(
            $container->get(UserProviderInterface::class),
            $container->get(SessionLogin::class),
            $container->get(TwoFactorMethods::class),
            $container->get(LoginThrottleInterface::class),
            $container->get(AuthEventDispatcher::class),
            $container->get(SystemClock::class),
            (int) ($container->get('config')['doctrine-auth']['two_factor']['lifetime'] ?? 300),
        );
    }
}
