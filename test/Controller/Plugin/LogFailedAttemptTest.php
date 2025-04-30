<?php

declare(strict_types=1);

namespace FwsDoctrineAuthTest\Controller\Plugin;

use Doctrine\ORM\Exception\ORMException;
use Doctrine\Persistence\Mapping\MappingException;
use Exception;
use FwsDoctrineAuth\Controller\Plugin\LogFailedAttempt;
use FwsDoctrineAuth\Exception\DoctrineAuthException;
use FwsDoctrineAuth\Model\GetClientIpAddress;
use FwsDoctrineAuthTest\Controller\TestAsset\SampleController;
use FwsDoctrineAuthTest\EntityManagerMockTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(LogFailedAttempt::class)]
class LogFailedAttemptTest extends TestCase
{
    use EntityManagerMockTrait;

    protected GetClientIpAddress|MockObject $clientIpAddressMock;
    protected SampleController $controller;
    protected string $testIp           = '127.0.0.1';
    protected string $testEmailAddress = 'test@example.com';

    public function setUp(): void
    {
        parent::setUp();

        $this->getEntityManagerMock([
            'persist',
            'flush',
            'clear',
        ]);

        $this->clientIpAddressMock = $this->getMockBuilder(GetClientIpAddress::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'getClientIP',
            ])
            ->getMock();

        $this->controller = new SampleController();
        $this->controller->getPluginManager()->setService(
            'logFailedLoginAttempt',
            new LogFailedAttempt(
                $this->entityManagerMock,
                $this->clientIpAddressMock
            )
        );
    }

    /**
     * Test log failed login attempt, no client ip
     *
     * @group controller-plugins
     * @group log-failed-attempt-plugin
     */
    public function testLogFailedLoginAttemptNoClientIp(): void
    {
        $this->clientIpAddressMock->expects($this->once())->method('getClientIP')->willReturn(null);

        $this->entityManagerMock->expects($this->never())->method('persist');
        $this->entityManagerMock->expects($this->never())->method('flush');
        $this->entityManagerMock->expects($this->never())->method('clear');

        $this->expectException(DoctrineAuthException::class);
        $this->expectExceptionMessage('Client IP address not found');
        $this->controller->logFailedLoginAttempt($this->testEmailAddress);
    }

    /**
     * Test log failed login attempt, persist log entity failed
     *
     * @group controller-plugins
     * @group log-failed-attempt-plugin
     */
    public function testLogFailedLoginAttemptPersistFailed(): void
    {
        $this->clientIpAddressMock->expects($this->once())->method('getClientIP')->willReturn($this->testIp);
        $this->entityManagerMock->expects($this->once())->method('persist')->willThrowException(new ORMException());

        $this->entityManagerMock->expects($this->never())->method('flush');
        $this->entityManagerMock->expects($this->never())->method('clear');

        $response = $this->controller->logFailedLoginAttempt($this->testEmailAddress);
        $this->assertFalse($response);
    }

    /**
     * Test log failed login attempt, flush log entity failed, clear entity manager failed
     *
     * @group controller-plugins
     * @group log-failed-attempt-plugin
     */
    public function testLogFailedLoginAttemptFlushFailedEntityManagerClearFailed(): void
    {
        $this->clientIpAddressMock->expects($this->once())->method('getClientIP')->willReturn($this->testIp);
        $this->entityManagerMock->expects($this->once())->method('persist');
        $this->entityManagerMock->expects($this->once())->method('flush')->willThrowException(new Exception());
        $this->entityManagerMock->expects($this->once())->method('clear')->willThrowException(new MappingException());

        $response = $this->controller->logFailedLoginAttempt($this->testEmailAddress);
        $this->assertFalse($response);
    }

    /**
     * Test log failed login attempt, flush log entity failed, clear entity manager succeeds
     *
     * @group controller-plugins
     * @group log-failed-attempt-plugin
     */
    public function testLogFailedLoginAttemptFlushFailedEntityManagerCleared(): void
    {
        $this->clientIpAddressMock->expects($this->once())->method('getClientIP')->willReturn($this->testIp);
        $this->entityManagerMock->expects($this->once())->method('persist');
        $this->entityManagerMock->expects($this->once())->method('flush')->willThrowException(new Exception());
        $this->entityManagerMock->expects($this->once())->method('clear');

        $response = $this->controller->logFailedLoginAttempt($this->testEmailAddress);
        $this->assertFalse($response);
    }

    /**
     * Test log failed login attempt succeeds
     *
     * @group controller-plugins
     * @group log-failed-attempt-plugin
     */
    public function testLogFailedLoginAttemptSucceeds(): void
    {
        $this->clientIpAddressMock->expects($this->once())->method('getClientIP')->willReturn($this->testIp);
        $this->entityManagerMock->expects($this->once())->method('persist');
        $this->entityManagerMock->expects($this->once())->method('flush');

        $response = $this->controller->logFailedLoginAttempt($this->testEmailAddress);
        $this->assertTrue($response);
    }
}
