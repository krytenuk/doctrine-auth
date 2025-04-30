<?php

declare(strict_types=1);

namespace FwsDoctrineAuthTest\Form\Service;

use Doctrine\ORM\EntityManager;
use FwsDoctrineAuth\Exception\DoctrineAuthException;
use FwsDoctrineAuth\Form;
use FwsDoctrineAuth\Form\Service\DoctrineAuthFormFactory;
use FwsDoctrineAuthTest\ApplicationTestTrait;
use FwsDoctrineAuthTest\EntityManagerMockTrait;
use Laminas\ServiceManager\ServiceLocatorInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

use function array_merge;

#[CoversClass(DoctrineAuthFormFactory::class)]
class DoctrineAuthFormFactoryTest extends TestCase
{
    use ApplicationTestTrait;
    use EntityManagerMockTrait;

    protected ServiceLocatorInterface $container;

    private array $config;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->container = $this->getApplicationServiceLocator();
    }

    /**
     * Test Doctrine Auth form factory - no config
     *
     * @group form-factories
     * @return void
     * @throws ContainerExceptionInterface
     * @throws DoctrineAuthException
     * @throws NotFoundExceptionInterface
     */
    public function testDoctrineAuthFormFactoryNoConfig()
    {
        $container = $this->getApplicationServiceLocator();
        $container->setService('config', []);

        $this->expectException(DoctrineAuthException::class);
        $this->expectExceptionMessage('"doctrineAuth" key not found in config');
        $factory = new DoctrineAuthFormFactory();
        $form    = $factory($container, DoctrineAuthFormFactory::REGISTRATION_FORM);
    }

    /**
     * Test Doctrine Auth form factory - form not set in config
     *
     * @group form-factories
     * @return void
     * @throws ContainerExceptionInterface
     * @throws DoctrineAuthException
     * @throws NotFoundExceptionInterface
     */
    public function testDoctrineAuthFormFactoryFormNotInConfig()
    {
        $container = $this->getApplicationServiceLocator();

        $dummyFormAlias = 'dummy-form';
        $this->expectException(DoctrineAuthException::class);
        $this->expectExceptionMessage("\"$dummyFormAlias\" key not found in config");
        $factory = new DoctrineAuthFormFactory();
        $form    = $factory($container, $dummyFormAlias);
    }

    /**
     * Test Doctrine Auth form factory - form not set in config
     *
     * @group form-factories
     * @return void
     * @throws ContainerExceptionInterface
     * @throws DoctrineAuthException
     * @throws NotFoundExceptionInterface
     */
    public function testDoctrineAuthFormFactoryFormClassNotExist()
    {
        $dummyForm      = 'FwsDoctrineAuth\Form\NotExistForm';
        $dummyFormAlias = 'dummy-form';
        $this->container->setService('config', array_merge(
            $this->getConfig(),
            ['doctrineAuth' => [$dummyFormAlias => $dummyForm]]
        ));

        $this->expectException(DoctrineAuthException::class);
        $this->expectExceptionMessage("Class \"$dummyForm\" not found");
        $factory = new DoctrineAuthFormFactory();
        $form    = $factory($this->container, $dummyFormAlias);
    }

    /**
     * Test Doctrine Auth form factory - FormElementManager does not have form registered
     *
     * @group form-factories
     * @return void
     * @throws DoctrineAuthException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testDoctrineAuthFormFactoryFormElementManagerNotHaveForm()
    {
        $testForm = Form\RegisterForm::class;
        $this->container->get('FormElementManager')->setFactory($testForm, null);

        $this->expectException(DoctrineAuthException::class);
        $this->expectExceptionMessage("Doctrine auth form \"$testForm\" not found");
        $factory = new DoctrineAuthFormFactory();
        $form    = $factory($this->container, DoctrineAuthFormFactory::REGISTRATION_FORM);
    }

    /**
     * Test Doctrine Auth form factory
     *
     * @group form-factories
     * @return void
     * @throws DoctrineAuthException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testDoctrineAuthFormFactoryForms()
    {
        $forms = [
            Form\Service\DoctrineAuthFormFactory::REGISTRATION_FORM                   => Form\RegisterForm::class,
            Form\Service\DoctrineAuthFormFactory::LOGIN_FORM                          => Form\LoginForm::class,
            Form\Service\DoctrineAuthFormFactory::FORGOTTEN_PASSWORD_FORM             => Form\ForgottenPasswordForm::class,
            Form\Service\DoctrineAuthFormFactory::RESET_PASSWORD_FORM                 => Form\ResetPasswordForm::class,
            Form\Service\DoctrineAuthFormFactory::SELECT_2FA_METHODS_FORM             => Form\SelectTwoFactorAuthMethodForm::class,
            Form\Service\DoctrineAuthFormFactory::TWO_FACTOR_AUTHENTICATION_CODE_FORM => Form\TwoFactorAuthenticationCodeForm::class,
        ];

        $container = $this->getApplicationServiceLocator();
        $container->setService(EntityManager::class, $this->getEntityManagerMock());

        foreach ($forms as $alias => $formClass) {
            $factory = new DoctrineAuthFormFactory();
            $form    = $factory($container, $alias);

            $this->assertInstanceOf($formClass, $form);

            $this->setUp();
        }
    }
}
