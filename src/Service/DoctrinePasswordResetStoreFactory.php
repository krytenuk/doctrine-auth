<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\Service;

use FwsDoctrineAuth\Doctrine\DoctrinePasswordResetStore;
use Psr\Container\ContainerInterface;

final class DoctrinePasswordResetStoreFactory
{
    public function __invoke(ContainerInterface $container): DoctrinePasswordResetStore
    {
        return new DoctrinePasswordResetStore(EntityManagerLocator::get($container));
    }
}
