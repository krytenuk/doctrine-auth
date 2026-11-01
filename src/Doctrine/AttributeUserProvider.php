<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\Doctrine;

use Closure;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use FwsDoctrineAuth\Exception\DoctrineAuthException;
use FwsDoctrineAuth\Mapping\IsDeletedProperty;
use FwsDoctrineAuth\User\UserProviderInterface;

use function array_filter;
use function count;
use function mb_strtolower;
use function reset;
use function sprintf;

/**
 * Finds users through the attributes on your user entity.
 */
final class AttributeUserProvider implements UserProviderInterface
{
    private ?UserMapping $mapping = null;

    /**
     * @param class-string $userClass
     * @param (Closure(string, string, string, string): string)|null $blindIndexer Given the user class, property,
     *        value and index name, returns the blind index of the value
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly string $userClass,
        private readonly ?Closure $blindIndexer = null,
    ) {
    }

    public function findByIdentity(string $identity): iterable
    {
        $mapping    = $this->mapping();
        $repository = $this->entityManager->getRepository($this->userClass);
        $blindIndex = $mapping->identity->blindIndex;

        if ($blindIndex === null) {
            return $repository->findBy([$mapping->identityProperty => $identity]);
        }

        if ($this->blindIndexer === null) {
            throw new DoctrineAuthException(sprintf(
                '%s::$%s uses a blind index, which needs krytenuk/doctrine-crypt',
                $this->userClass,
                $mapping->identityProperty
            ));
        }

        $index = ($this->blindIndexer)($this->userClass, $mapping->identityProperty, $identity, $blindIndex);

        /** A short blind index matches other values too, so only the users who really have this identity count */
        return array_filter(
            $repository->findBy([$blindIndex => $index]),
            fn (object $user): bool => mb_strtolower($this->getIdentity($user)) === mb_strtolower($identity)
        );
    }

    public function findById(string $id): ?object
    {
        return $this->entityManager->find($this->userClass, $id);
    }

    public function getId(object $user): string
    {
        $identifiers = $this->metadata()->getIdentifierValues($user);
        if (count($identifiers) !== 1) {
            throw new DoctrineAuthException(sprintf('%s must have a single identifier', $this->userClass));
        }

        return (string) reset($identifiers);
    }

    public function getIdentity(object $user): string
    {
        return (string) $this->metadata()->getFieldValue($user, $this->mapping()->identityProperty);
    }

    public function getPasswordHash(object $user): ?string
    {
        return $this->metadata()->getFieldValue($user, $this->mapping()->credentialProperty);
    }

    public function setPasswordHash(object $user, string $hash): void
    {
        $this->metadata()->setFieldValue($user, $this->mapping()->credentialProperty, $hash);
        $this->entityManager->flush();
    }

    public function isEnabled(object $user): bool
    {
        $mapping = $this->mapping();

        if (
            $mapping->activeProperty !== null
            && $this->metadata()->getFieldValue($user, $mapping->activeProperty) !== $mapping->active->activeValue
        ) {
            return false;
        }

        if ($mapping->deletedProperty === null) {
            return true;
        }

        $value = $this->metadata()->getFieldValue($user, $mapping->deletedProperty);
        if ($mapping->deleted->deletedValue === IsDeletedProperty::ANY_DATE) {
            return ! $value instanceof DateTimeInterface;
        }

        return $value !== $mapping->deleted->deletedValue;
    }

    private function mapping(): UserMapping
    {
        return $this->mapping ??= UserMapping::fromClass($this->userClass);
    }

    private function metadata(): ClassMetadata
    {
        return $this->entityManager->getClassMetadata($this->userClass);
    }
}
