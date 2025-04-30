<?php

declare(strict_types=1);

namespace FwsDoctrineAuthTest\Entity\Repository;

use DateTime;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use FwsDoctrineAuth\Entity\Repository\IpBlockedRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(IpBlockedRepository::class)]
class IpBlockedRepositoryTest extends TestCase
{
    /**
     * Test delete blocked ip address with no date
     *
     * @group doctrine-entity-repositories
     * @return void
     */
    public function testDeleteBlockedIpAddressNoDate()
    {
        $testIpAddress = '127.0.0.1';
        $testDate      = new DateTime('1970-01-01 00:00:00');

        $queryMock = $this->getMockBuilder(AbstractQuery::class)
            ->disableOriginalConstructor()
            ->getMock();
        $queryMock->expects($this->once())->method('getSingleScalarResult');

        $queryBuilderMock = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['expr', 'delete', 'from', 'setParameter', 'getQuery', 'where', 'andWhere'])
            ->getMock();
        $queryBuilderMock->method('getQuery')->willReturn($queryMock);
        $queryBuilderMock->method('expr')->willReturnCallback(function () {
            return new Expr();
        });

        $queryBuilderMock->expects($this->once())->method('setParameter')->willReturnCallback(function ($field, $value) use ($queryBuilderMock, $testIpAddress) {
            $this->assertSame('ipAddress', $field);
            $this->assertSame($testIpAddress, $value);
            return $queryBuilderMock;
        });

        $queryBuilderMock->expects($this->once())->method('delete')->willReturnCallback(function ($delete, $alias) use ($queryBuilderMock) {
            $this->assertNull($delete);
            $this->assertNull($alias);
            return $queryBuilderMock;
        });

        $queryBuilderMock->expects($this->once())->method('where')->willReturnCallback(function (Expr\Comparison $comp) use ($queryBuilderMock) {
            $this->assertSame('ipb.ipAddress', $comp->getLeftExpr());
            $this->assertSame(Expr\Comparison::EQ, $comp->getOperator());
            $this->assertSame(':ipAddress', $comp->getRightExpr());
            return $queryBuilderMock;
        });

        $repositoryMock = $this->getMockBuilder(IpBlockedRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();

        $repositoryMock->expects($this->once())->method('createQueryBuilder')->willReturnCallback(function ($alias) use ($queryBuilderMock) {
            $this->assertSame('ipb', $alias);
            return $queryBuilderMock;
        });

        $repositoryMock->deleteBlockedIpAddress($testIpAddress);
    }

    /**
     * Test delete blocked ip address with date
     *
     * @group doctrine-entity-repositories
     * @return void
     */
    public function testDeleteBlockedIpAddressWithDate()
    {
        $testIpAddress = '127.0.0.1';
        $testDate      = new DateTime('1970-01-01 00:00:00');

        $queryMock = $this->getMockBuilder(AbstractQuery::class)
            ->disableOriginalConstructor()
            ->getMock();
        $queryMock->expects($this->once())->method('getSingleScalarResult');

        $queryBuilderMock = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['expr', 'delete', 'from', 'setParameter', 'getQuery', 'where', 'andWhere'])
            ->getMock();
        $queryBuilderMock->method('getQuery')->willReturn($queryMock);
        $queryBuilderMock->method('expr')->willReturnCallback(function () {
            return new Expr();
        });

        $queryBuilderMock->expects($this->exactly(2))->method('setParameter')->willReturnCallback(function ($field, $value) use ($queryBuilderMock, $testIpAddress, $testDate) {
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

        $queryBuilderMock->expects($this->once())->method('delete')->willReturnCallback(function ($delete, $alias) use ($queryBuilderMock) {
            $this->assertNull($delete);
            $this->assertNull($alias);
            return $queryBuilderMock;
        });

        $queryBuilderMock->expects($this->once())->method('where')->willReturnCallback(function (Expr\Comparison $comp) use ($queryBuilderMock) {
            $this->assertSame('ipb.ipAddress', $comp->getLeftExpr());
            $this->assertSame(Expr\Comparison::EQ, $comp->getOperator());
            $this->assertSame(':ipAddress', $comp->getRightExpr());
            return $queryBuilderMock;
        });

        $queryBuilderMock->expects($this->once())->method('andWhere')->willReturnCallback(function (Expr\Comparison $comp) use ($queryBuilderMock) {
            $this->assertSame('ipb.dateBlocked', $comp->getLeftExpr());
            $this->assertSame(Expr\Comparison::LT, $comp->getOperator());
            $this->assertSame(':date', $comp->getRightExpr());
            return $queryBuilderMock;
        });

        $repositoryMock = $this->getMockBuilder(IpBlockedRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();

        $repositoryMock->expects($this->once())->method('createQueryBuilder')->willReturnCallback(function ($alias) use ($queryBuilderMock) {
            $this->assertSame('ipb', $alias);
            return $queryBuilderMock;
        });

        $repositoryMock->deleteBlockedIpAddress($testIpAddress, $testDate);
    }
}
