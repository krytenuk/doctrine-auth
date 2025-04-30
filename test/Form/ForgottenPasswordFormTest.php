<?php

declare(strict_types=1);

namespace FwsDoctrineAuthTest\Form;

use Doctrine\ORM\EntityManager;
use FwsDoctrineAuth\Entity\BaseUser;
use FwsDoctrineAuth\Form\ForgottenPasswordForm;
use FwsDoctrineAuthTest\ApplicationTestTrait;
use FwsDoctrineAuthTest\DoctrineRepositoryMockTrait;
use FwsDoctrineAuthTest\EntityManagerMockTrait;
use Laminas\Form\Element;
use Laminas\Form\FormInterface;
use Laminas\InputFilter\InputFilter;
use Laminas\Stdlib\Parameters;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

#[CoversClass(ForgottenPasswordForm::class)]
class ForgottenPasswordFormTest extends TestCase
{
    use ApplicationTestTrait;
    use DoctrineRepositoryMockTrait;
    use EntityManagerMockTrait;

    private ForgottenPasswordForm $form;
    private InputFilter $inputFilter;
    private string $testEmail;
    private string $csrfCode;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function setUp(): void
    {
        parent::setUp();

        $container = $this->getApplicationServiceLocator();
        $container->setService(EntityManager::class, $this->getEntityManagerMock());

        $this->form = $container->get('FormElementManager')->get(ForgottenPasswordForm::class);

        $this->entityManagerMock
            ->expects($this->any())
            ->method('getRepository')
            ->willReturn($this->getRepositoryMock(
                ['findOneBy']
            ));

        $this->inputFilter = $this->form->getInputFilter();

        $this->csrfCode  = $this->form->get('csrf')->getCsrfValidator()->getHash();
        $this->testEmail = 'test@example.com';
    }

    /**
     * Get test data
     */
    private function getData(): Parameters
    {
        return new Parameters([
            'submit'       => 'submit',
            'emailAddress' => $this->testEmail,
            'csrf'         => $this->csrfCode,
        ]);
    }

    /**
     * Test forgotten password form elements are set
     *
     * @group forms
     * @return void
     */
    public function testForgottenPasswordFormElements()
    {
        $this->assertInstanceOf(ForgottenPasswordForm::class, $this->form);
        $this->assertInstanceOf(FormInterface::class, $this->form);

        /* Email address form element */
        $this->assertTrue($this->form->has('emailAddress'));
        $this->assertInstanceOf(Element\Email::class, $this->form->get('emailAddress'));

        /* CSRF form element */
        $this->assertTrue($this->form->has('csrf'));
        $this->assertInstanceOf(Element\Csrf::class, $this->form->get('csrf'));

        /* Submit button element */
        $this->assertTrue($this->form->has('submit'));
        $this->assertInstanceOf(Element\Submit::class, $this->form->get('submit'));
    }

    /**
     * Test forgotten password form attributes
     *
     * @group forms
     * @return void
     */
    public function testForgottenPasswordFormAttributes()
    {
        $this->assertEquals('POST', $this->form->getAttribute('method'));
        $this->assertEquals('reset-password', $this->form->getName());
    }

    /**
     * Test validator email address empty
     *
     * @group forms
     * @return void
     */
    public function testForgottenPasswordFormValidatorIdentityNotEmpty()
    {
        $data = $this->getData();

        $data->set('emailAddress', null);
        $this->inputFilter->setData($data);
        $this->assertFalse($this->inputFilter->isValid($data));
    }

    /**
     * Test validator email address invalid
     *
     * @group forms
     * @return void
     */
    public function testForgottenPasswordFormValidatorIdentityInvalid()
    {
        $data = $this->getData();

        $data->set('emailAddress', ['10000001']);
        $this->inputFilter->setData($data);
        $this->assertFalse($this->inputFilter->isValid());
    }

    /**
     * Test validator email address invalid format
     *
     * @group forms
     * @return void
     */
    public function testForgottenPasswordFormValidatorIdentityInvalidFormat()
    {
        $data = $this->getData();

        $data->set('emailAddress', 'invalid');
        $this->inputFilter->setData($data);
        $this->assertFalse($this->inputFilter->isValid());
    }

    /**
     * Test validator email address invalid hostname
     *
     * @group forms
     * @return void
     */
    public function testForgottenPasswordFormValidatorIdentityInvalidHostname()
    {
        $data = $this->getData();

        $data->set('emailAddress', 'user@domain.test');
        $this->inputFilter->setData($data);
        $this->assertFalse($this->inputFilter->isValid());
    }

    /**
     * Test validator no csrf token
     *
     * @group forms
     * @return void
     */
    public function testForgottenPasswordFormValidatorNoCsrf()
    {
        $this->repositoryMock
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['emailAddress' => $this->testEmail])
            ->willReturn(new BaseUser());

        $data = $this->getData();

        $data->set('csrf', null);
        $this->inputFilter->setData($data);
        $this->assertFalse($this->inputFilter->isValid());
    }

    /**
     * Test validator csrf token invalid
     *
     * @group forms
     * @return void
     */
    public function testForgottenPasswordFormValidatorInvalidCsrf()
    {
        $this->repositoryMock
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['emailAddress' => $this->testEmail])
            ->willReturn(new BaseUser());

        $data = $this->getData();

        $data->set('csrf', 'invalid');
        $this->inputFilter->setData($data);
        $this->assertFalse($this->inputFilter->isValid());
    }

    /**
     * Test validator valid
     *
     * @group forms
     * @return void
     */
    public function testForgottenPasswordFormValidationPass()
    {
        $this->repositoryMock
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['emailAddress' => $this->testEmail])
            ->willReturn(new BaseUser());

        $data = $this->getData();

        $this->inputFilter->setData($data);
        $this->assertTrue($this->inputFilter->isValid());
    }
}
