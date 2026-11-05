<?php

declare(strict_types=1);

namespace FwsDoctrineAuthTest\Integration;

use Closure;
use Doctrine\DBAL\Driver\PDO\SQLite\Driver;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\Tools\SchemaTool;
use FwsDoctrineAuth\ConfigProvider;
use FwsDoctrineAuth\Entity\FailedLoginAttemptsLog;
use FwsDoctrineAuth\Entity\LoginLog;
use FwsDoctrineAuth\Exception\DoctrineAuthException;
use FwsDoctrineAuth\Login\LoginService;
use FwsDoctrineAuth\PasswordReset\AuthMailerInterface;
use FwsDoctrineAuth\PasswordReset\PasswordResetService;
use FwsDoctrineAuth\PasswordReset\PasswordResetStatus;
use FwsDoctrineAuth\Mezzio\SessionAuthentication;
use FwsDoctrineAuth\Mezzio\SessionLogin;
use FwsDoctrineAuthTest\Support\Entity\User;
use FwsDoctrineAuthTest\Support\RecordingMailer;
use Laminas\Diactoros\ResponseFactory;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Stdlib\ArrayUtils;
use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Authentication\ConfigProvider as AuthenticationConfigProvider;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Roave\PsrContainerDoctrine\EntityManagerFactory;

/**
 * Builds the module the way a Mezzio application does: a real container, the ConfigProvider and the
 * roave/psr-container-doctrine factories.
 */
#[RequiresPhpExtension('pdo_sqlite')]
final class ContainerWiringTest extends TestCase
{
    private const ENTITY_MANAGER = 'doctrine.entity_manager.orm_default';

    private function container(array $appConfig = []): ServiceManager
    {
        $config = ArrayUtils::merge((new AuthenticationConfigProvider())(), (new ConfigProvider())());
        $config = ArrayUtils::merge($config, [
            'doctrine' => [
                'connection'    => [
                    'orm_default' => [
                        'driver_class' => Driver::class,
                        'params'       => ['memory' => true],
                    ],
                ],
                'configuration' => [
                    'orm_default' => ['proxy_dir' => sys_get_temp_dir(), 'auto_generate_proxy_classes' => true],
                ],
                'driver'        => [
                    'orm_default' => [
                        'class' => AttributeDriver::class,
                        'paths' => [__DIR__ . '/../Support/Entity'],
                    ],
                ],
            ],
            'doctrine-auth' => [
                'user_class' => User::class,
                'throttle'   => ['secret' => 'test-secret'],
            ],
        ]);
        $config = ArrayUtils::merge($config, $appConfig);

        $dependencies = $config['dependencies'];
        $dependencies['factories'][self::ENTITY_MANAGER]        = EntityManagerFactory::class;
        $dependencies['aliases'][EntityManagerInterface::class] = self::ENTITY_MANAGER;
        $dependencies['invokables'][ResponseFactoryInterface::class] = ResponseFactory::class;
        $dependencies['services']['config']                     = $config;
        $dependencies['allow_override']                         = true;

        return new ServiceManager($dependencies);
    }

    private function createSchema(ServiceManager $container): EntityManagerInterface
    {
        $entityManager = $container->get(self::ENTITY_MANAGER);
        (new SchemaTool($entityManager))->createSchema([
            $entityManager->getClassMetadata(User::class),
            $entityManager->getClassMetadata(LoginLog::class),
            $entityManager->getClassMetadata(FailedLoginAttemptsLog::class),
            $entityManager->getClassMetadata(\FwsDoctrineAuth\Entity\IpBlocked::class),
            $entityManager->getClassMetadata(\FwsDoctrineAuth\Entity\PasswordReminder::class),
        ]);

        return $entityManager;
    }

    public function testLoginWorksThroughTheContainerAndIsLogged(): void
    {
        $container     = $this->container();
        $entityManager = $this->createSchema($container);
        $entityManager->persist(new User('a@example.com', password_hash('correct', PASSWORD_BCRYPT, ['cost' => 4])));
        $entityManager->flush();

        $service = $container->get(LoginService::class);

        self::assertFalse($service->attempt('a@example.com', 'wrong', '203.0.113.5')->isSuccess());
        self::assertSame(1, $entityManager->getRepository(FailedLoginAttemptsLog::class)->count([]));

        self::assertTrue($service->attempt('a@example.com', 'correct', '203.0.113.5')->isSuccess());
        self::assertSame(0, $entityManager->getRepository(FailedLoginAttemptsLog::class)->count([]));
        self::assertSame(1, $entityManager->getRepository(LoginLog::class)->count([]));
    }

    public function testTheLoginLogCanBeTurnedOff(): void
    {
        $container     = $this->container(['doctrine-auth' => ['listeners' => [
            \FwsDoctrineAuth\Event\LoginSucceeded::class => ['login-log' => null],
        ]]]);
        $entityManager = $this->createSchema($container);
        $entityManager->persist(new User('a@example.com', password_hash('correct', PASSWORD_BCRYPT, ['cost' => 4])));
        $entityManager->flush();

        self::assertTrue($container->get(LoginService::class)->attempt('a@example.com', 'correct', '203.0.113.5')->isSuccess());
        self::assertSame(0, $entityManager->getRepository(LoginLog::class)->count([]));
    }

    public function testMezzioServicesResolve(): void
    {
        $container = $this->container();

        self::assertInstanceOf(SessionAuthentication::class, $container->get(AuthenticationInterface::class));
        self::assertInstanceOf(SessionLogin::class, $container->get(SessionLogin::class));
    }

    public function testPasswordResetWorksThroughTheContainer(): void
    {
        $mailer    = new RecordingMailer();
        $container = $this->container();
        $container->setService(AuthMailerInterface::class, $mailer);
        $entityManager = $this->createSchema($container);
        $user          = new User('a@example.com', password_hash('old', PASSWORD_BCRYPT, ['cost' => 4]));
        $entityManager->persist($user);
        $entityManager->flush();

        $service = $container->get(PasswordResetService::class);
        $service->request('a@example.com', '203.0.113.5');

        self::assertSame(PasswordResetStatus::Success, $service->reset($mailer->lastToken(), 'new', '203.0.113.5'));
        self::assertTrue(password_verify('new', (string) $user->getPassword()));
        self::assertTrue($container->get(LoginService::class)->attempt('a@example.com', 'new', '203.0.113.5')->isSuccess());
    }

    public function testTwoFactorMethodsFromTheConfigAreUsedAtLogin(): void
    {
        $container = $this->container(['doctrine-auth' => ['two_factor' => ['methods' => ['fake' => 'fake-method']]]]);
        $container->setService('fake-method', new \FwsDoctrineAuthTest\Support\FakeTwoFactorMethod(['1']));
        $entityManager = $this->createSchema($container);
        $entityManager->persist(new User('a@example.com', password_hash('correct', PASSWORD_BCRYPT, ['cost' => 4])));
        $entityManager->flush();

        $result = $container->get(LoginService::class)->attempt('a@example.com', 'correct', '203.0.113.5');
        self::assertTrue($result->requiresTwoFactor());
        self::assertSame(0, $entityManager->getRepository(LoginLog::class)->count([]));

        $twoFactor = $container->get(\FwsDoctrineAuth\Mezzio\TwoFactorLogin::class);
        $session   = $twoFactor->begin(new \Mezzio\Session\Session([]), $result->user);

        self::assertTrue($twoFactor->verify($session, 'fake', '123456', '203.0.113.5')->isSuccess());
        self::assertSame(1, $entityManager->getRepository(LoginLog::class)->count([]));
    }

    public function testMissingUserClassFailsClearly(): void
    {
        $config = (new ConfigProvider())();
        $dependencies = $config['dependencies'];
        $dependencies['services']['config'] = $config;
        $dependencies['services'][EntityManagerInterface::class] = $this->createStub(EntityManagerInterface::class);

        try {
            (new ServiceManager($dependencies))->get(LoginService::class);
            self::fail('Expected an exception');
        } catch (\Throwable $exception) {
            $previous = $exception;
            while ($previous->getPrevious() !== null) {
                $previous = $previous->getPrevious();
            }
            self::assertInstanceOf(DoctrineAuthException::class, $previous);
            self::assertStringContainsString('user_class', $previous->getMessage());
        }
    }

    public function testConfigCanBeCached(): void
    {
        $config = (new ConfigProvider())();

        array_walk_recursive($config, static function (mixed $value): void {
            self::assertNotInstanceOf(Closure::class, $value);
        });
    }
}
