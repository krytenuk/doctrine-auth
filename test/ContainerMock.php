<?php

declare(strict_types=1);

namespace FwsDoctrineAuthTest;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

use function array_key_exists;

/**
 * Mock Psr Container interface
 *
 * @see ContainerInterface
 */
class ContainerMock extends TestCase implements ContainerInterface
{
    protected array $storage = [];

    public function __construct()
    {
        parent::__construct('container');
    }

    /**
     * Add mock object to the container
     *
     * @param string $className full qualified class name of the class to mock
     * @return $this
     */
    public function addMockObject(string $className, ?string $alias = null): ContainerMock
    {
        $this->storage[$className] = $this->getMockBuilder($className)
            ->disableOriginalConstructor()
            ->getMock();

        if ($alias) {
            $this->storage[$alias] = $this->storage[$className];
        }

        return $this;
    }

    /**
     * Get container value
     */
    public function get(string $id): mixed
    {
        if (array_key_exists($id, $this->storage)) {
            return $this->storage[$id];
        }
        return null;
    }

    public function has(string $id): bool
    {
        return array_key_exists($id, $this->storage);
    }

    public function set(string $id, mixed $value): ContainerMock
    {
        $this->storage[$id] = $value;
        return $this;
    }
}
