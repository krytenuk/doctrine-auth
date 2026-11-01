<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\Service;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;

final class EntityManagerLocator
{
    public static function get(ContainerInterface $container): EntityManagerInterface
    {
        $config = $container->get('config');

        return $container->get($config['doctrine-auth']['entity_manager'] ?? EntityManagerInterface::class);
    }
}
