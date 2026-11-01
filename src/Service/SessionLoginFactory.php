<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\Service;

use FwsDoctrineAuth\Mezzio\SessionLogin;
use FwsDoctrineAuth\User\UserProviderInterface;
use Psr\Container\ContainerInterface;

final class SessionLoginFactory
{
    public function __invoke(ContainerInterface $container): SessionLogin
    {
        return new SessionLogin($container->get(UserProviderInterface::class));
    }
}
