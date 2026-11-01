<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\Service;

use FwsDoctrineAuth\Mezzio\RolesProviderInterface;
use FwsDoctrineAuth\Mezzio\SessionAuthentication;
use FwsDoctrineAuth\Mezzio\SessionLogin;
use FwsDoctrineAuth\User\UserProviderInterface;
use Mezzio\Authentication\UserInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;

final class SessionAuthenticationFactory
{
    public function __invoke(ContainerInterface $container): SessionAuthentication
    {
        return new SessionAuthentication(
            $container->get(UserProviderInterface::class),
            $container->get(SessionLogin::class),
            $container->get(RolesProviderInterface::class),
            $container->get(ResponseFactoryInterface::class),
            $container->get(UserInterface::class),
            $container->get('config')['doctrine-auth']['login_path'] ?? null,
        );
    }
}
