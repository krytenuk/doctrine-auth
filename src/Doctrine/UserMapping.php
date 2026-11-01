<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\Doctrine;

use FwsDoctrineAuth\Exception\DoctrineAuthException;
use FwsDoctrineAuth\Mapping\CredentialProperty;
use FwsDoctrineAuth\Mapping\IdentityProperty;
use FwsDoctrineAuth\Mapping\IsActiveProperty;
use FwsDoctrineAuth\Mapping\IsDeletedProperty;
use ReflectionClass;
use ReflectionProperty;

use function sprintf;

/**
 * The properties of a user entity, as declared by its attributes.
 */
final class UserMapping
{
    private function __construct(
        public readonly string $identityProperty,
        public readonly IdentityProperty $identity,
        public readonly string $credentialProperty,
        public readonly ?string $activeProperty,
        public readonly ?IsActiveProperty $active,
        public readonly ?string $deletedProperty,
        public readonly ?IsDeletedProperty $deleted,
    ) {
    }

    /**
     * @throws DoctrineAuthException if the attributes are missing or repeated
     */
    public static function fromClass(string $className): self
    {
        $found = [];
        foreach (self::properties($className) as $property) {
            foreach ([IdentityProperty::class, CredentialProperty::class, IsActiveProperty::class, IsDeletedProperty::class] as $attribute) {
                foreach ($property->getAttributes($attribute) as $reflectionAttribute) {
                    if (isset($found[$attribute])) {
                        throw new DoctrineAuthException(sprintf('%s has more than one %s attribute', $className, $attribute));
                    }
                    $found[$attribute] = [$property->getName(), $reflectionAttribute->newInstance()];
                }
            }
        }

        foreach ([IdentityProperty::class, CredentialProperty::class] as $required) {
            if (! isset($found[$required])) {
                throw new DoctrineAuthException(sprintf('%s needs a property with the %s attribute', $className, $required));
            }
        }

        return new self(
            $found[IdentityProperty::class][0],
            $found[IdentityProperty::class][1],
            $found[CredentialProperty::class][0],
            $found[IsActiveProperty::class][0] ?? null,
            $found[IsActiveProperty::class][1] ?? null,
            $found[IsDeletedProperty::class][0] ?? null,
            $found[IsDeletedProperty::class][1] ?? null,
        );
    }

    /**
     * All properties including the private ones of parent classes, which getProperties() leaves out.
     *
     * @return array<string, ReflectionProperty>
     */
    private static function properties(string $className): array
    {
        $properties = [];
        $class      = new ReflectionClass($className);
        do {
            foreach ($class->getProperties() as $property) {
                $properties[$property->getName()] ??= $property;
            }
        } while ($class = $class->getParentClass());

        return $properties;
    }
}
