<?php

declare(strict_types=1);

namespace FwsDoctrineAuthTest;

use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\MockObject\MockObject;

trait EntityManagerMockTrait
{
    protected EntityManager|MockObject|null $entityManagerMock;
    /**
     * @return mixed
     */
    public function getEntityManagerMock(array $methods = []): MockObject
    {
        if (! $this->entityManagerMock) {
            /** @var TestCase $this */
            $entityManager = $this->getMockBuilder(EntityManager::class)
                ->disableOriginalConstructor();

            if ($methods) {
                $entityManager->onlyMethods($methods);
            }

            $this->entityManagerMock = $entityManager->getMock();
        }

        return $this->entityManagerMock;
    }
}
