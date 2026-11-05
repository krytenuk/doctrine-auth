<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\TwoFactor;

use FwsDoctrineAuth\Exception\DoctrineAuthException;
use Psr\Container\ContainerInterface;

use function array_filter;
use function array_keys;
use function array_values;

/**
 * The methods in the doctrine-auth.two_factor.methods config. Each is fetched from the container when first used.
 */
final class TwoFactorMethods
{
    /**
     * @param array<string, string|null> $methods Name to container service. A null service removes the method
     */
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly array $methods,
    ) {
    }

    /**
     * @return list<string>
     */
    public function names(): array
    {
        return array_keys(array_filter($this->methods, static fn (?string $service): bool => $service !== null));
    }

    public function get(string $name): ?TwoFactorMethodInterface
    {
        $service = $this->methods[$name] ?? null;
        if ($service === null) {
            return null;
        }

        $method = $this->container->get($service);
        if (! $method instanceof TwoFactorMethodInterface) {
            throw new DoctrineAuthException(
                'Two factor method "' . $name . '" must implement ' . TwoFactorMethodInterface::class
            );
        }

        return $method;
    }

    /**
     * The names of the methods the user has set up.
     *
     * @return list<string>
     */
    public function enabledFor(object $user): array
    {
        return array_values(array_filter(
            $this->names(),
            fn (string $name): bool => (bool) $this->get($name)?->isEnabledFor($user)
        ));
    }
}
