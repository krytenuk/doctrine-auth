<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\Service;

use FwsDoctrineAuth\Event\AuthEventDispatcher;
use FwsDoctrineAuth\Login\LoginService;
use FwsDoctrineAuth\Password\PasswordHasherInterface;
use FwsDoctrineAuth\Throttle\LoginThrottleInterface;
use FwsDoctrineAuth\TwoFactor\TwoFactorPolicyInterface;
use FwsDoctrineAuth\User\UserProviderInterface;
use Psr\Container\ContainerInterface;

final class LoginServiceFactory
{
    public function __invoke(ContainerInterface $container): LoginService
    {
        return new LoginService(
            $container->get(UserProviderInterface::class),
            $container->get(PasswordHasherInterface::class),
            $container->get(LoginThrottleInterface::class),
            $container->get(TwoFactorPolicyInterface::class),
            $container->get(AuthEventDispatcher::class),
        );
    }
}
