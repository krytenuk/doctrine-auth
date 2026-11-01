<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\Service;

use FwsDoctrineAuth\Clock\SystemClock;
use FwsDoctrineAuth\Doctrine\LoginLogListener;
use Psr\Container\ContainerInterface;

final class LoginLogListenerFactory
{
    public function __invoke(ContainerInterface $container): LoginLogListener
    {
        return new LoginLogListener(EntityManagerLocator::get($container), $container->get(SystemClock::class));
    }
}
