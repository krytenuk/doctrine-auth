<?php

declare(strict_types=1);

namespace FwsDoctrineAuthTest\Controller\Plugin;

use Doctrine\ORM\Exception\ORMException;
use Doctrine\Persistence\Mapping\MappingException;
use Exception;
use FwsDoctrineAuth\Controller\Plugin\BlockIP;
use FwsDoctrineAuth\Entity\IpBlocked;
use FwsDoctrineAuth\Entity\Repository\FailedLoginAttemptsLogRepository;
use FwsDoctrineAuth\Exception\DoctrineAuthException;
use FwsDoctrineAuth\Model\GetClientIpAddress;
use FwsDoctrineAuthTest\Controller\TestAsset\SampleController;
use FwsDoctrineAuthTest\EntityManagerMockTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

use function sprintf;

#[CoversClass(BlockIP::class)]
class BlockIPTest extends TestCase
{
    use EntityManagerMockTrait;

    protected GetClientIpAddress|MockObject $clientIpAddressMock;
    protected FailedLoginAttemptsLogRepository $failedLoginAttemptsLogRepository;
    protected SampleController $controller;

    protected string $testIp           = '127.0.0.1';
    protected string $testEmailAddress = 'test@example.com';

    public function setUp(): void
    {
        parent::setUp();

        $this->getEntityManagerMock([
            'getRepository',
            'persist',
            'flush',
            'clear',
        ]);

        $this->failedLoginAttemptsLogRepository = $this->getMockBuilder(FailedLoginAttemptsLogRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'countFailedAttempts',
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

    public function initPlugin(array $config = []): void
    {
        $this->controller->getPluginManager()->setService(
            'blockIpAddress',
            new BlockIP(
                $this->entityManagerMock,
                $this->clientIpAddressMock,
                $config
            )
        );
    }

    /**
     * Test Block Ip Controller plugin, max attempts time config not set
     *
     * @group controller-plugins
     * @group block-ip-plugin
     */
    public function testBlockIpMaxLoginAttemptsTimeConfigNotSet(): void
    {
        $this->initPlugin();

        $this->entityManagerMock->expects($this->never())->method('getRepository');
        $this->failedLoginAttemptsLogRepository->expects($this->never())->method('countFailedAttempts');
        $this->entityManagerMock->expects($this->never())->method('persist');
        $this->entityManagerMock->expects($this->never())->method('flush');
        $this->entityManagerMock->expects($this->never())->method('clear');

        $this->expectException(DoctrineAuthException::class);
        $this->expectExceptionMessage('maxLoginAttemptsTime config key not set');
        $this->controller->blockIpAddress($this->testEmailAddress);
    }

    /**
     * Test Block Ip Controller plugin, max attempts time config not set
     *
     * @group controller-plugins
     * @group block-ip-plugin
     */
    public function testBlockIpMaxLoginAttemptsTimeConfigZero(): void
    {
        $config = [
            'doctrineAuth' => [
                'maxLoginAttemptsTime' => 0,
            ],
        ];
        $this->initPlugin($config);

        $this->clientIpAddressMock->expects($this->never())->method('getClientIP');
        $this->entityManagerMock->expects($this->never())->method('getRepository');
        $this->failedLoginAttemptsLogRepository->expects($this->never())->method('countFailedAttempts');
        $this->entityManagerMock->expects($this->never())->method('persist');
        $this->entityManagerMock->expects($this->never())->method('flush');
        $this->entityManagerMock->expects($this->never())->method('clear');

        $this->expectException(DoctrineAuthException::class);
        $this->expectExceptionMessage('maxLoginAttemptsTime config key not set');
        $this->controller->blockIpAddress($this->testEmailAddress);
    }

    /**
     * Test Block Ip Controller plugin, max attempts config not set
     *
     * @group controller-plugins
     * @group block-ip-plugin
     */
    public function testBlockIpMaxAttemptsConfigNotSet(): void
    {
        $config = [
            'doctrineAuth' => [
                'maxLoginAttemptsTime' => 10,
            ],
        ];
        $this->initPlugin($config);

        $this->clientIpAddressMock->expects($this->never())->method('getClientIP');
        $this->entityManagerMock->expects($this->never())->method('getRepository');
        $this->failedLoginAttemptsLogRepository->expects($this->never())->method('countFailedAttempts');
        $this->entityManagerMock->expects($this->never())->method('persist');
        $this->entityManagerMock->expects($this->never())->method('flush');
        $this->entityManagerMock->expects($this->never())->method('clear');

        $this->expectException(DoctrineAuthException::class);
        $this->expectExceptionMessage('maxLoginAttempts config key not set');
        $this->controller->blockIpAddress($this->testEmailAddress);
    }

    /**
     * Test Block Ip Controller plugin, max attempts config not set
     *
     * @group controller-plugins
     * @group block-ip-plugin
     */
    public function testBlockIpMaxAttemptsSetZero(): void
    {
        $config = [
            'doctrineAuth' => [
                'maxLoginAttemptsTime' => 10,
                'maxLoginAttempts'     => 0,
            ],
        ];
        $this->initPlugin($config);

        $this->clientIpAddressMock->expects($this->never())->method('getClientIP');
        $this->entityManagerMock->expects($this->never())->method('getRepository');
        $this->failedLoginAttemptsLogRepository->expects($this->never())->method('countFailedAttempts');
        $this->entityManagerMock->expects($this->never())->method('persist');
        $this->entityManagerMock->expects($this->never())->method('flush');
        $this->entityManagerMock->expects($this->never())->method('clear');

        $result = $this->controller->blockIpAddress($this->testEmailAddress);
        $this->assertFalse($result);
    }

    /**
     * Test Block Ip Controller plugin, max attempts config not set
     *
     * @group controller-plugins
     * @group block-ip-plugin
     */
    public function testBlockIpClientIpNotSet(): void
    {
        $config = [
            'doctrineAuth' => [
                'maxLoginAttemptsTime' => 10,
                'maxLoginAttempts'     => 3,
            ],
        ];
        $this->initPlugin($config);

        $this->clientIpAddressMock->expects($this->once())->method('getClientIP')->willReturn(null);

        $this->entityManagerMock->expects($this->never())->method('getRepository');
        $this->failedLoginAttemptsLogRepository->expects($this->never())->method('countFailedAttempts');
        $this->entityManagerMock->expects($this->never())->method('persist');
        $this->entityManagerMock->expects($this->never())->method('flush');
        $this->entityManagerMock->expects($this->never())->method('clear');

        $this->expectException(DoctrineAuthException::class);
        $this->expectExceptionMessage('Client IP address not found');
        $this->controller->blockIpAddress($this->testEmailAddress);
    }

    /**
     * Test Block Ip Controller plugin, no failed attempts logged
     *
     * @group controller-plugins
     * @group block-ip-plugin
     */
    public function testBlockIpLoginAttemptsNull(): void
    {
        $config = [
            'doctrineAuth' => [
                'maxLoginAttemptsTime' => 10,
                'maxLoginAttempts'     => 3,
            ],
        ];
        $this->initPlugin($config);

        $this->clientIpAddressMock->expects($this->once())->method('getClientIP')->willReturn($this->testIp);
        $this->entityManagerMock->expects($this->once())->method('getRepository')->willReturn($this->failedLoginAttemptsLogRepository);
        $this->failedLoginAttemptsLogRepository->expects($this->once())->method('countFailedAttempts')->willReturn(null);

        $this->entityManagerMock->expects($this->never())->method('persist');
        $this->entityManagerMock->expects($this->never())->method('flush');
        $this->entityManagerMock->expects($this->never())->method('clear');

        $return = $this->controller->blockIpAddress($this->testEmailAddress);
        $this->assertTrue($return);
    }

    /**
     * Test Block Ip Controller plugin, failed attempts less than max allowed in config
     *
     * @group controller-plugins
     * @group block-ip-plugin
     */
    public function testBlockIpLoginAttemptsLessThanMaxAllowed(): void
    {
        $config = [
            'doctrineAuth' => [
                'maxLoginAttemptsTime' => 10,
                'maxLoginAttempts'     => 3,
            ],
        ];
        $this->initPlugin($config);

        $loginAttempts = $config['doctrineAuth']['maxLoginAttempts'] - 1;
        $this->clientIpAddressMock->expects($this->once())->method('getClientIP')->willReturn($this->testIp);
        $this->entityManagerMock->expects($this->once())->method('getRepository')->willReturn($this->failedLoginAttemptsLogRepository);
        $this->failedLoginAttemptsLogRepository->expects($this->once())->method('countFailedAttempts')->willReturn($loginAttempts);

        $this->entityManagerMock->expects($this->never())->method('persist');
        $this->entityManagerMock->expects($this->never())->method('flush');
        $this->entityManagerMock->expects($this->never())->method('clear');

        $return = $this->controller->blockIpAddress($this->testEmailAddress);
        $this->assertTrue($return);
    }

    /**
     * Test Block Ip Controller plugin, persist entity failed
     *
     * @group controller-plugins
     * @group block-ip-plugin
     */
    public function testBlockIpFailedPersistFailed(): void
    {
        $config = [
            'doctrineAuth' => [
                'maxLoginAttemptsTime' => 10,
                'maxLoginAttempts'     => 3,
            ],
        ];
        $this->initPlugin($config);

        $loginAttempts = $config['doctrineAuth']['maxLoginAttempts'] + 1;
        $this->clientIpAddressMock->expects($this->once())->method('getClientIP')->willReturn($this->testIp);
        $this->entityManagerMock->expects($this->once())->method('getRepository')->willReturn($this->failedLoginAttemptsLogRepository);
        $this->failedLoginAttemptsLogRepository->expects($this->once())->method('countFailedAttempts')->willReturn($loginAttempts);
        $this->entityManagerMock->expects($this->once())->method('persist')->willThrowException(new ORMException());

        $this->entityManagerMock->expects($this->never())->method('flush');
        $this->entityManagerMock->expects($this->never())->method('clear');

        $this->expectException(DoctrineAuthException::class);
        $this->expectExceptionMessage(sprintf('Unable to persist %s', IpBlocked::class));
        $this->controller->blockIpAddress($this->testEmailAddress);
    }

    /**
     * Test Block Ip Controller plugin, log block IP failed, entity manager clear failed
     *
     * @group controller-plugins
     * @group block-ip-plugin
     */
    public function testBlockIpFailedEntityManagerClearFailed(): void
    {
        $config = [
            'doctrineAuth' => [
                'maxLoginAttemptsTime' => 10,
                'maxLoginAttempts'     => 3,
            ],
        ];
        $this->initPlugin($config);

        $loginAttempts = $config['doctrineAuth']['maxLoginAttempts'] + 1;
        $this->clientIpAddressMock->expects($this->once())->method('getClientIP')->willReturn($this->testIp);
        $this->entityManagerMock->expects($this->once())->method('getRepository')->willReturn($this->failedLoginAttemptsLogRepository);
        $this->failedLoginAttemptsLogRepository->expects($this->once())->method('countFailedAttempts')->willReturn($loginAttempts);
        $this->entityManagerMock->expects($this->once())->method('persist');
        $this->entityManagerMock->expects($this->once())->method('flush')->willThrowException(new Exception());
        $this->entityManagerMock->expects($this->once())->method('clear')->willThrowException(new MappingException());

        $this->expectException(DoctrineAuthException::class);
        $this->expectExceptionMessage('Unable to block IP address');
        $this->controller->blockIpAddress($this->testEmailAddress);
    }

    /**
     * Test Block Ip Controller plugin, block IP failed, entity manager cleared
     *
     * @group controller-plugins
     * @group block-ip-plugin
     */
    public function testBlockIpBlockIpFailedEntityManagerCleared(): void
    {
        $config = [
            'doctrineAuth' => [
                'maxLoginAttemptsTime' => 10,
                'maxLoginAttempts'     => 3,
            ],
        ];
        $this->initPlugin($config);

        $loginAttempts = $config['doctrineAuth']['maxLoginAttempts'] + 1;
        $this->clientIpAddressMock->expects($this->once())->method('getClientIP')->willReturn($this->testIp);
        $this->entityManagerMock->expects($this->once())->method('getRepository')->willReturn($this->failedLoginAttemptsLogRepository);
        $this->failedLoginAttemptsLogRepository->expects($this->once())->method('countFailedAttempts')->willReturn($loginAttempts);
        $this->entityManagerMock->expects($this->once())->method('persist');
        $this->entityManagerMock->expects($this->once())->method('flush')->willThrowException(new Exception());
        $this->entityManagerMock->expects($this->once())->method('clear');

        $this->expectException(DoctrineAuthException::class);
        $this->expectExceptionMessage('Unable to block IP address');
        $this->controller->blockIpAddress($this->testEmailAddress);
    }

    /**
     * Test Block Ip Controller plugin, IP logged
     *
     * @group controller-plugins
     * @group block-ip-plugin
     */
    public function testBlockIpAttemptLogged(): void
    {
        $config = [
            'doctrineAuth' => [
                'maxLoginAttemptsTime' => 10,
                'maxLoginAttempts'     => 3,
            ],
        ];
        $this->initPlugin($config);

        $loginAttempts = $config['doctrineAuth']['maxLoginAttempts'] + 1;
        $this->clientIpAddressMock->expects($this->once())->method('getClientIP')->willReturn($this->testIp);
        $this->entityManagerMock->expects($this->once())->method('getRepository')->willReturn($this->failedLoginAttemptsLogRepository);
        $this->failedLoginAttemptsLogRepository->expects($this->once())->method('countFailedAttempts')->willReturn($loginAttempts);
        $this->entityManagerMock->expects($this->once())->method('persist');
        $this->entityManagerMock->expects($this->once())->method('flush');

        $this->entityManagerMock->expects($this->never())->method('clear');

        $return = $this->controller->blockIpAddress($this->testEmailAddress);
        $this->assertTrue($return);
    }
}
