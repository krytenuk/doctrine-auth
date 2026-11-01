<?php

declare(strict_types=1);

namespace FwsDoctrineAuth;

use FwsDoctrineAuth\Clock\SystemClock;
use FwsDoctrineAuth\Doctrine\AttributeUserProvider;
use FwsDoctrineAuth\Doctrine\DoctrineLoginThrottle;
use FwsDoctrineAuth\Doctrine\LoginLogListener;
use FwsDoctrineAuth\Event\AuthEventDispatcher;
use FwsDoctrineAuth\Event\LoginSucceeded;
use FwsDoctrineAuth\Login\LoginService;
use FwsDoctrineAuth\Mezzio\NoRolesProvider;
use FwsDoctrineAuth\Mezzio\RolesProviderInterface;
use FwsDoctrineAuth\Mezzio\SessionAuthentication;
use FwsDoctrineAuth\Mezzio\SessionLogin;
use FwsDoctrineAuth\Password\NativePasswordHasher;
use FwsDoctrineAuth\Password\PasswordHasherInterface;
use FwsDoctrineAuth\Throttle\LoginThrottleInterface;
use FwsDoctrineAuth\TwoFactor\NoTwoFactorPolicy;
use FwsDoctrineAuth\TwoFactor\TwoFactorPolicyInterface;
use FwsDoctrineAuth\User\UserProviderInterface;
use Mezzio\Authentication\AuthenticationInterface;

/**
 * Every interface below is an alias, so an application can replace any part by pointing it at its own class.
 */
final class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies'  => $this->getDependencies(),
            'doctrine'      => $this->getDoctrineConfig(),
            'doctrine-auth' => $this->getDefaults(),
        ];
    }

    public function getDependencies(): array
    {
        return [
            'aliases'    => [
                AuthenticationInterface::class  => SessionAuthentication::class,
                LoginThrottleInterface::class   => DoctrineLoginThrottle::class,
                PasswordHasherInterface::class  => NativePasswordHasher::class,
                RolesProviderInterface::class   => NoRolesProvider::class,
                TwoFactorPolicyInterface::class => NoTwoFactorPolicy::class,
                UserProviderInterface::class    => AttributeUserProvider::class,
            ],
            'invokables' => [
                NativePasswordHasher::class => NativePasswordHasher::class,
                NoRolesProvider::class      => NoRolesProvider::class,
                NoTwoFactorPolicy::class    => NoTwoFactorPolicy::class,
                SystemClock::class          => SystemClock::class,
            ],
            'factories'  => [
                AttributeUserProvider::class => Service\AttributeUserProviderFactory::class,
                AuthEventDispatcher::class   => Service\AuthEventDispatcherFactory::class,
                DoctrineLoginThrottle::class => Service\DoctrineLoginThrottleFactory::class,
                LoginLogListener::class      => Service\LoginLogListenerFactory::class,
                LoginService::class          => Service\LoginServiceFactory::class,
                SessionAuthentication::class => Service\SessionAuthenticationFactory::class,
                SessionLogin::class          => Service\SessionLoginFactory::class,
            ],
        ];
    }

    /**
     * Adds the module's entities to the default entity manager's attribute driver.
     */
    public function getDoctrineConfig(): array
    {
        return [
            'driver' => [
                'orm_default' => [
                    'paths' => [__DIR__ . '/Entity'],
                ],
            ],
        ];
    }

    public function getDefaults(): array
    {
        return [
            'throttle'  => [
                'account' => [
                    'max_attempts' => 5,
                    'window'       => 900,
                ],
                'ip'      => [
                    'max_attempts' => 50,
                    'window'       => 900,
                    'block_for'    => 3600,
                ],
            ],
            'listeners' => [
                LoginSucceeded::class => [
                    'login-log' => LoginLogListener::class,
                ],
            ],
        ];
    }
}
