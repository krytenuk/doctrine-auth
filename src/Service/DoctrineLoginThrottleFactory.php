<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\Service;

use FwsDoctrineAuth\Clock\SystemClock;
use FwsDoctrineAuth\Doctrine\DoctrineLoginThrottle;
use Psr\Container\ContainerInterface;

final class DoctrineLoginThrottleFactory
{
    public function __invoke(ContainerInterface $container): DoctrineLoginThrottle
    {
        $throttle = $container->get('config')['doctrine-auth']['throttle'] ?? [];

        return new DoctrineLoginThrottle(
            EntityManagerLocator::get($container),
            $container->get(SystemClock::class),
            ['account' => $throttle['account'], 'ip' => $throttle['ip']],
            (string) ($throttle['secret'] ?? '')
        );
    }
}
