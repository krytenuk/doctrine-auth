<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\Service;

use FwsDoctrineAuth\Clock\SystemClock;
use FwsDoctrineAuth\Event\AuthEventDispatcher;
use FwsDoctrineAuth\Password\PasswordHasherInterface;
use FwsDoctrineAuth\PasswordReset\AuthMailerInterface;
use FwsDoctrineAuth\PasswordReset\PasswordResetService;
use FwsDoctrineAuth\PasswordReset\PasswordResetStoreInterface;
use FwsDoctrineAuth\Throttle\LoginThrottleInterface;
use FwsDoctrineAuth\User\UserProviderInterface;
use Psr\Container\ContainerInterface;

final class PasswordResetServiceFactory
{
    public function __invoke(ContainerInterface $container): PasswordResetService
    {
        return new PasswordResetService(
            $container->get(UserProviderInterface::class),
            $container->get(PasswordHasherInterface::class),
            $container->get(PasswordResetStoreInterface::class),
            $container->get(AuthMailerInterface::class),
            $container->get(LoginThrottleInterface::class),
            $container->get(AuthEventDispatcher::class),
            $container->get(SystemClock::class),
            $container->get('config')['doctrine-auth']['password_reset'],
        );
    }
}
