<?php

declare(strict_types=1);

namespace FwsDoctrineAuthTest\Entity\Repository;

use DateTime;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use FwsDoctrineAuth\Entity\Repository\FailedLoginAttemptsLogRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(FailedLoginAttemptsLogRepository::class)]
class FailedLoginAttemptsLogRepositoryTest extends TestCase
{
    /**
     * Test count failed login attempts
     *
     * @group doctrine-entity-repositories
     */
    public function testCountFailedAttempts(): void
    {
        $testIpAddress = '127.0.0.1';
        $testDate      = new DateTime('1970-01-01 00:00:00');

        $queryMock = $this->getMockBuilder(AbstractQuery::class)
            ->disableOriginalConstructor()
            ->getMock();
        $queryMock->expects($this->once())->method('getSingleScalarResult');

        $queryBuilderMock = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['expr', 'select', 'setParameter', 'getQuery', 'join', 'andWhere', 'where'])
            ->getMock();
        $queryBuilderMock->method('getQuery')->willReturn($queryMock);
        $queryBuilderMock->method('expr')->willReturnCallback(function () {
            return new Expr();
        });

        $queryBuilderMock->expects($this->exactly(2))->method('setParameter')->willReturnCallback(function ($field, $value) use ($queryBuilderMock, $testDate, $testIpAddress) {
            switch ($field) {
                case 'ipAddress':
                    $this->assertSame($testIpAddress, $value);
                    break;
                case 'date';
                    $this->assertSame($testDate, $value);
                    break;
            }
            return $queryBuilderMock;
        });

        $queryBuilderMock->expects($this->once())->method('select')->willReturnCallback(function (Expr\Func $func) use ($queryBuilderMock) {
            $this->assertSame('COUNT', $func->getName());
            $this->assertSame([0 => 'la'], $func->getArguments());
            return $queryBuilderMock;
        });

        $queryBuilderMock->expects($this->once())->method('where')->willReturnCallback(function (Expr\Comparison $comp) use ($queryBuilderMock) {
            $this->assertSame('la.ipAddress', $comp->getLeftExpr());
            $this->assertSame(Expr\Comparison::EQ, $comp->getOperator());
            $this->assertSame(':ipAddress', $comp->getRightExpr());
            return $queryBuilderMock;
        });

        $queryBuilderMock->expects($this->once())->method('andWhere')->willReturnCallback(function (Expr\Comparison $comp) use ($queryBuilderMock) {
            $this->assertSame('la.dateLogged', $comp->getLeftExpr());
            $this->assertSame(Expr\Comparison::GT, $comp->getOperator());
            $this->assertSame(':date', $comp->getRightExpr());
            return $queryBuilderMock;
        });

        $repositoryMock = $this->getMockBuilder(FailedLoginAttemptsLogRepository::class)
                ->disableOriginalConstructor()
                ->onlyMethods(['createQueryBuilder'])
                ->getMock();

        $repositoryMock->expects($this->once())->method('createQueryBuilder')->willReturnCallback(function ($alias) use ($queryBuilderMock) {
            $this->assertSame('la', $alias);
            return $queryBuilderMock;
        });

        $repositoryMock->countFailedAttempts($testIpAddress, $testDate);
    }
}
