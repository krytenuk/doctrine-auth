<?php

namespace FwsDoctrineAuth\Model;

use Doctrine\ORM\EntityManager;
use FwsDoctrineAuth\Exception\DoctrineAuthException;
use FwsDoctrineAuth\Mapping\IdentityClass;
use ReflectionClass;

class EntityAttributes
{
    /**
     * Entity property attributes cache
     */
    private array $attributes = [];

    public function __construct(
        protected readonly EntityManager                   $entityManager,
    )
    {
    }

    public function gatAttributes(): array
    {
        /**
         * @var string[] $entitiesClassNames
         */
        $entitiesClassNames = $this->entityManager->getConfiguration()->getMetadataDriverImpl()->getAllClassNames();

        foreach ($entitiesClassNames as $entityFQCN) {
            /** Get doctrine auth attributes for entity */
            $attributes = $this->getAttributes($entityFQCN);

        }
    }

    /**
     * @throws DoctrineAuthException
     */
    protected function getAttributes(string $entityFQCN): ?array
    {
        if (!class_exists($entityFQCN)) {
            return null;
        }

        if (array_key_exists($entityFQCN, $this->attributes)) {
            return $this->attributes[$entityFQCN];
        }

        $reflectionEntity = new ReflectionClass($entityFQCN);
        $attributes = [];
        $properties = $reflectionEntity->getProperties();
        foreach ($properties as $property) {
            $identityClass = $property->getAttributes(IdentityClass::class);
            if (empty($identityClass)) {
                continue;
            }

            if (count($identityClass) > 1) {
                throw new DoctrineAuthException(sprintf(
                    'Multiple IdentityClass attributes found in %s. Only one IdentityClass in total is supported.',
                    $entityFQCN
                ));
            }

            $attributes[$property->getName()] = array_pop($identityClass)?->newInstance();
        }
    }
}