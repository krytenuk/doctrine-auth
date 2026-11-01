<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\Service;

use FwsDoctrineAuth\Doctrine\AttributeUserProvider;
use FwsDoctrineAuth\Exception\DoctrineAuthException;
use FwsDoctrineCrypt\Model\Crypt;
use Psr\Container\ContainerInterface;

final class AttributeUserProviderFactory
{
    public function __invoke(ContainerInterface $container): AttributeUserProvider
    {
        $userClass = $container->get('config')['doctrine-auth']['user_class'] ?? null;
        if ($userClass === null) {
            throw new DoctrineAuthException('doctrine-auth.user_class is not set');
        }

        $blindIndexer = null;
        if ($container->has(Crypt::class)) {
            $blindIndexer = static fn (string $class, string $property, string $value, string $index): string
                => $container->get(Crypt::class)->getIndex($class, $property, $value, $index);
        }

        return new AttributeUserProvider(EntityManagerLocator::get($container), $userClass, $blindIndexer);
    }
}
