<?php

declare(strict_types=1);

namespace FwsDoctrineAuthTest\Controller\Plugin;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\Persistence\Mapping\MappingException;
use Exception;
use FwsDoctrineAuth\Controller\Plugin\LogSuccessfulLogin;
use FwsDoctrineAuth\Entity\AuthUserInterface;
use FwsDoctrineAuth\Entity\BaseUser;
use FwsDoctrineAuth\Entity\LoginLog;
use FwsDoctrineAuthTest\Controller\TestAsset\SampleController;
use FwsDoctrineAuthTest\EntityManagerMockTrait;
use Laminas\Authentication\AuthenticationService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[CoversClass(LogSuccessfulLogin::class)]
class LogSuccessfulLoginTest extends TestCase
{
    use EntityManagerMockTrait;

    protected AuthenticationService|MockObject $authenticationServiceMock;
    protected EntityRepository|MockObject $entityRepositoryMock;
    protected AuthUserInterface $identity;
    protected SampleController $controller;

    public function setUp(): void
    {
        parent::setUp();

        $this->getEntityManagerMock([
            'persist',
            'flush',
            'clear',
        ]);

        $this->authenticationServiceMock = $this->getMockBuilder(AuthenticationService::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $this->entityRepositoryMock = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'findOneBy',
            ])
            ->getMock();

        $this->identity = new BaseUser();
        $this->identity->setEmailAddress('test@example.com');
        $reflection = new ReflectionClass($this->identity);
        $property   = $reflection->getProperty('userId');
        $property->setValue($this->identity, 1);
        $property->setAccessible(false);

        $this->controller = new SampleController();
        $this->controller->getPluginManager()->setService(
            'logSuccessfulLogin',
            new LogSuccessfulLogin(
                $this->entityManagerMock,
                $this->authenticationServiceMock
            )
        );
    }

    /**
     * Test log successful login, no identity
     *
     * @group controller-plugins
     * @group log-successful-login-plugin
     * @return void
     */
    public function testSuccessfulLoginNoIdentity()
    {
        $this->entityManagerMock->expects($this->never())->method('persist');
        $this->entityManagerMock->expects($this->never())->method('flush');
        $this->entityManagerMock->expects($this->never())->method('clear');

        $response = $this->controller->logSuccessfulLogin(null, false);
        $this->assertNull($response);
    }

    /**
     * Test log successful login, persist log entity failed
     *
     * @group controller-plugins
     * @group log-successful-login-plugin
     * @return void
     */
    public function testSuccessfulLoginPersistFailed()
    {
        $this->entityManagerMock->expects($this->once())->method('persist')->with($this->isInstanceOf(LoginLog::class))->willThrowException(new ORMException());

        $this->entityManagerMock->expects($this->never())->method('flush');
        $this->entityManagerMock->expects($this->never())->method('clear');

        $response = $this->controller->logSuccessfulLogin($this->identity, false);
        $this->assertNull($response);
    }

    /**
     * Test log successful login, flush and clear entity manager failed
     *
     * @group controller-plugins
     * @group log-successful-login-plugin
     * @return void
     */
    public function testSuccessfulLoginFlushAndClearEntityManagerFailed()
    {
        $this->entityManagerMock->expects($this->once())->method('persist')->with($this->isInstanceOf(LoginLog::class));
        $this->entityManagerMock->expects($this->once())->method('flush')->willThrowException(new Exception());
        $this->entityManagerMock->expects($this->once())->method('clear')->willThrowException(new MappingException());

        $response = $this->controller->logSuccessfulLogin($this->identity, false);
        $this->assertNull($response);
    }

    /**
     * Test log successful login, flush entity manager failed
     *
     * @group controller-plugins
     * @group log-successful-login-plugin
     * @return void
     */
    public function testSuccessfulLoginFlushEntityManagerFailed()
    {
        $this->entityManagerMock->expects($this->once())->method('persist')->with($this->isInstanceOf(LoginLog::class));
        $this->entityManagerMock->expects($this->once())->method('flush')->willThrowException(new Exception());
        $this->entityManagerMock->expects($this->once())->method('clear');

        $response = $this->controller->logSuccessfulLogin($this->identity, false);
        $this->assertNull($response);
    }

    /**
     * Test log successful login, clear entity manager failed
     *
     * @group controller-plugins
     * @group log-successful-login-plugin
     * @return void
     */
    public function testSuccessfulLoginLogged()
    {
        $this->entityManagerMock->expects($this->once())->method('persist')->with($this->isInstanceOf(LoginLog::class));
        $this->entityManagerMock->expects($this->once())->method('flush');

        $this->entityManagerMock->expects($this->never())->method('clear');

        $response = $this->controller->logSuccessfulLogin($this->identity, false);
        $this->assertNull($response);
    }
}
