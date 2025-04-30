<?php

declare(strict_types=1);

namespace FwsDoctrineAuthTest\Entity\Repository;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use FwsDoctrineAuth\Entity\Repository\UserRoleRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(UserRoleRepository::class)]
class UserRoleRepositoryTest extends TestCase
{
    /**
     * Test find all user roles
     *
     * @group doctrine-entity-repositories
     * @return void
     */
    public function testFindAllArray()
    {
        $queryMock = $this->getMockBuilder(AbstractQuery::class)
            ->disableOriginalConstructor()
            ->getMock();
        $queryMock->expects($this->once())->method('getResult');

        $queryBuilderMock = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['select', 'getQuery'])
            ->getMock();
        $queryBuilderMock->method('getQuery')->willReturn($queryMock);

        $queryBuilderMock->expects($this->once())->method('select')->willReturnCallback(function ($parameters) use ($queryBuilderMock) {
            $this->assertSame('r.userRoleId, r.role', $parameters);
            return $queryBuilderMock;
        });

        $repositoryMock = $this->getMockBuilder(UserRoleRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();

        $repositoryMock->expects($this->once())->method('createQueryBuilder')->willReturnCallback(function ($alias) use ($queryBuilderMock) {
            $this->assertSame('r', $alias);
            return $queryBuilderMock;
        });

        $repositoryMock->findAllArray();
    }
}
