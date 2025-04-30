<?php

declare(strict_types=1);

namespace FwsDoctrineAuthTest\Entity;

use Exception;
use FwsDoctrineAuth\Entity\EntityInterface;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;

use function method_exists;
use function property_exists;
use function sprintf;

abstract class AbstractTestCase extends TestCase
{
    /**
     * Test entity id method
     *
     * @throws ReflectionException
     * @throws Exception
     */
    protected function testId(EntityInterface $entity, string $idProperty, string $idGetter, int $testId): void
    {
        if (! property_exists($entity, $idProperty)) {
            throw new Exception(sprintf('Property %s does not exist in entity %s', $idProperty, $entity::class));
        }
        if (! method_exists($entity, $idGetter)) {
            throw new Exception(sprintf('Method %s does not exist in entity %s', $idGetter, $entity::class));
        }

        $this->assertNull($entity->$idGetter());
        $reflection = new ReflectionClass($entity);
        $property   = $reflection->getProperty($idProperty);
        $property->setValue($entity, $testId);
        $property->setAccessible(false);
        $this->assertEquals($testId, $entity->$idGetter());
    }
}
