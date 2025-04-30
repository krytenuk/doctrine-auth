<?php

declare(strict_types=1);

namespace FwsDoctrineAuthTest\Controller\Plugin;

use DateTime;
use FwsDoctrineAuth\Controller\Plugin\IsIpBlocked;
use FwsDoctrineAuth\Entity\IpBlocked;
use FwsDoctrineAuth\Entity\Repository\IpBlockedRepository;
use FwsDoctrineAuth\Exception\DoctrineAuthException;
use FwsDoctrineAuth\Model\GetClientIpAddress;
use FwsDoctrineAuthTest\Controller\TestAsset\SampleController;
use FwsDoctrineAuthTest\EntityManagerMockTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(IsIpBlocked::class)]
class IsIpBlockedTest extends TestCase
{
    use EntityManagerMockTrait;

    protected IpBlockedRepository $ipBlockedRepositoryMock;
    protected GetClientIpAddress $clientIpAddressMock;
    protected SampleController $controller;
    protected string $testIp = '127.0.0.1';
    protected array $config  = [];

    public function setUp(): void
    {
        $this->getEntityManagerMock([
            'getRepository',
        ]);

        $this->ipBlockedRepositoryMock = $this->getMockBuilder(IpBlockedRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'deleteBlockedIpAddress',
                'count',
            ])
            ->getMock();

        $this->clientIpAddressMock = $this->getMockBuilder(GetClientIpAddress::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'getClientIP',
            ])
            ->getMock();

        $this->controller = new SampleController();
    }

    protected function initPlugin(array $config = []): void
    {
        $this->controller->getPluginManager()->setService(
            'isIpBlocked',
            new IsIpBlocked(
                $this->entityManagerMock,
                $this->clientIpAddressMock,
                $config
            )
        );
    }

    /**
     * Test release time not set in config
     *
     * @group controller-plugins
     * @group is-ip-blocked-plugin
     * @return void
     */
    public function testIsIpBlockedReleaseTimeNotSet()
    {
        $this->initPlugin();

        $this->clientIpAddressMock->expects($this->never())->method('getClientIP');
        $this->entityManagerMock->expects($this->never())->method('getRepository');
        $this->ipBlockedRepositoryMock->expects($this->never())->method('deleteBlockedIpAddress');
        $this->ipBlockedRepositoryMock->expects($this->never())->method('count');

        $this->expectException(DoctrineAuthException::class);
        $this->expectExceptionMessage('loginReleaseTime config key not set');
        $this->controller->isIpBlocked();
    }

    /**
     * Test no client IP found
     *
     * @group controller-plugins
     * @group is-ip-blocked-plugin
     * @return void
     */
    public function testIsIpBlockedNoClientIp()
    {
        $config = [
            'doctrineAuth' => [
                'loginReleaseTime' => 0,
            ],
        ];
        $this->initPlugin($config);

        $this->clientIpAddressMock->expects($this->once())->method('getClientIP')->willReturn(null);

        $this->entityManagerMock->expects($this->never())->method('getRepository');
        $this->ipBlockedRepositoryMock->expects($this->never())->method('deleteBlockedIpAddress');
        $this->ipBlockedRepositoryMock->expects($this->never())->method('count');

        $this->expectException(DoctrineAuthException::class);
        $this->expectExceptionMessage('Client IP address not found');
        $this->controller->isIpBlocked();
    }

    /**
     * Test release time not set in config
     *
     * @group controller-plugins
     * @group is-ip-blocked-plugin
     * @return void
     */
    public function testIsIpBlockedReleaseTimeZero()
    {
        $config = [
            'doctrineAuth' => [
                'loginReleaseTime' => 0,
            ],
        ];
        $this->initPlugin($config);

        $this->entityManagerMock->expects($this->once())->method('getRepository')->with(IpBlocked::class)->willReturn($this->ipBlockedRepositoryMock);
        $this->clientIpAddressMock->expects($this->once())->method('getClientIP')->willReturn($this->testIp);
        $this->ipBlockedRepositoryMock->expects($this->once())->method('count');

        $this->ipBlockedRepositoryMock->expects($this->never())->method('deleteBlockedIpAddress');

        $response = $this->controller->isIpBlocked();
    }

    /**
     * Test release time not set in config
     *
     * @group controller-plugins
     * @group is-ip-blocked-plugin
     * @return void
     */
    public function testIsIpBlockedReleaseBlock()
    {
        $loginReleaseTime = 10;
        $config           = [
            'doctrineAuth' => [
                'loginReleaseTime' => $loginReleaseTime,
            ],
        ];
        $this->initPlugin($config);

        $this->entityManagerMock->expects($this->once())->method('getRepository')->with(IpBlocked::class)->willReturn($this->ipBlockedRepositoryMock);
        $this->clientIpAddressMock->expects($this->once())->method('getClientIP')->willReturn($this->testIp);
        $this->ipBlockedRepositoryMock->expects($this->once())->method('deleteBlockedIpAddress')->with($this->testIp, $this->isInstanceOf(DateTimeImmutable::class));
        $this->ipBlockedRepositoryMock->expects($this->once())->method('count');

        $response = $this->controller->isIpBlocked();
    }
}
