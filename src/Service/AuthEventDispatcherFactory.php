<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\Service;

use FwsDoctrineAuth\Event\AuthEventDispatcher;
use Psr\Container\ContainerInterface;

final class AuthEventDispatcherFactory
{
    public function __invoke(ContainerInterface $container): AuthEventDispatcher
    {
        return new AuthEventDispatcher($container, $container->get('config')['doctrine-auth']['listeners'] ?? []);
    }
}
