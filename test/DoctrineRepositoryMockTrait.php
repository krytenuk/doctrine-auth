<?php

declare(strict_types=1);

namespace FwsDoctrineAuthTest;

use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;

trait DoctrineRepositoryMockTrait
{
    private EntityRepository|MockObject|null $repositoryMock;
    public function getRepositoryMock(array $methods = []): EntityRepository|MockObject|null
    {
        if (! $this->repositoryMock) {
            $repository = $this->getMockBuilder(EntityRepository::class)
                ->disableOriginalConstructor();

            if ($methods) {
                $repository->onlyMethods($methods);
            }

            $this->repositoryMock = $repository->getMock();
        }

        return $this->repositoryMock;
    }
}
