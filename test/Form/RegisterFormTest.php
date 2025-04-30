<?php

declare(strict_types=1);

namespace FwsDoctrineAuthTest\Form;

use Doctrine\ORM\EntityManager;
use DoctrineModule\Validator\NoObjectExists;
use FwsDoctrineAuth\Entity\BaseUser;
use FwsDoctrineAuth\Form\AbstractDefaultForm;
use FwsDoctrineAuth\Form\RegisterForm;
use FwsDoctrineAuthTest\ApplicationTestTrait;
use FwsDoctrineAuthTest\DoctrineRepositoryMockTrait;
use FwsDoctrineAuthTest\EntityManagerMockTrait;
use Laminas\Form\Element;
use Laminas\Form\FormInterface;
use Laminas\InputFilter\InputFilter;
use Laminas\Stdlib\Parameters;
use Laminas\Validator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

#[CoversClass(RegisterForm::class)]
class RegisterFormTest extends TestCase
{
    use ApplicationTestTrait;
    use DoctrineRepositoryMockTrait;
    use EntityManagerMockTrait;

    private RegisterForm $form;
    private InputFilter $inputFilter;
    private string|null $identityProperty;
    private string|null $credentialProperty;
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

        $this->form = $container->get('FormElementManager')->get(RegisterForm::class);

        $this->entityManagerMock
            ->expects($this->any())
            ->method('getRepository')
            ->willReturn($this->getRepositoryMock(
                ['findOneBy']
            ));

        $this->inputFilter = $this->form->getInputFilter();

        $this->identityProperty = $this->getConfig()['doctrine']['authentication']['orm_default']['identity_property'] ?? null;
        $this->assertIsString($this->identityProperty);
        $this->assertNotEmpty($this->identityProperty);

        $this->credentialProperty = $this->getConfig()['doctrine']['authentication']['orm_default']['credential_property'] ?? null;
        $this->assertIsString($this->credentialProperty);
        $this->assertNotEmpty($this->credentialProperty);

        $this->csrfCode  = $this->form->get('csrf')->getCsrfValidator()->getHash();
        $this->testEmail = 'test@example.com';
    }

    /**
     * Get test data
     */
    private function getData(): Parameters
    {
        return new Parameters([
            $this->identityProperty   => $this->testEmail,
            $this->credentialProperty => 'password',
            'csrf'                    => $this->csrfCode,
            'submit'                  => 'submit',
        ]);
    }

    /**
     * Test register user form elements are set
     *
     * @group forms
     * @return void
     */
    public function testLoginFormElements()
    {
        $this->assertInstanceOf(RegisterForm::class, $this->form);
        $this->assertInstanceOf(AbstractDefaultForm::class, $this->form);
        $this->assertInstanceOf(FormInterface::class, $this->form);

        /* Email address form element */
        $this->assertTrue($this->form->has($this->identityProperty));
        $this->assertInstanceOf(Element\Email::class, $this->form->get($this->identityProperty));

        /* Password form element */
        $this->assertTrue($this->form->has($this->credentialProperty));
        $this->assertInstanceOf(Element\Password::class, $this->form->get($this->credentialProperty));

        /* Mobile number form element */
        $this->assertTrue($this->form->has('mobileNumber'));
        $this->assertInstanceOf(Element\Text::class, $this->form->get('mobileNumber'));

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
    public function testRegisterFormAttributes()
    {
        $this->assertEquals('POST', $this->form->getAttribute('method'));
        $this->assertEquals('auth', $this->form->getName());
    }

    /**
     * Test register form validators with empty email
     *
     * @group forms
     * @return void
     */
    public function testRegisterFormValidatorsNoEmail()
    {
        $data = $this->getData();

        unset($data[$this->identityProperty]);
        $this->inputFilter->setData($data);
        $this->assertFalse($this->inputFilter->isValid($data));
        $messages = $this->inputFilter->getMessages();
        $this->assertIsArray($messages);
        $this->assertCount(1, $messages);
        $this->assertArrayHasKey($this->identityProperty, $messages);
        $this->assertCount(1, $messages[$this->identityProperty]);
        $this->assertArrayHasKey(Validator\NotEmpty::IS_EMPTY, $messages[$this->identityProperty]);
    }

    /**
     * Test register form validators with invalid email address
     *
     * @group forms
     * @return void
     */
    public function testRegisterFormValidatorsInvalidEmail()
    {
        $data = $this->getData();

        $data[$this->identityProperty] = ['10000001'];
        $this->inputFilter->setData($data);
        $this->assertFalse($this->inputFilter->isValid());
        $messages = $this->inputFilter->getMessages();
        $this->assertIsArray($messages);
        $this->assertCount(1, $messages);
        $this->assertArrayHasKey($this->identityProperty, $messages);
        $this->assertCount(1, $messages[$this->identityProperty]);
        $this->assertArrayHasKey(Validator\EmailAddress::INVALID, $messages[$this->identityProperty]);
    }

    /**
     * Test register form validators with invalid format email address
     *
     * @group forms
     * @return void
     */
    public function testRegisterFormValidatorsInvalidEmailFormat()
    {
        $data = $this->getData();

        $data[$this->identityProperty] = 'invalid';
        $this->inputFilter->setData($data);
        $this->assertFalse($this->inputFilter->isValid());
        $messages = $this->inputFilter->getMessages();
        $this->assertIsArray($messages);
        $this->assertCount(1, $messages);
        $this->assertArrayHasKey($this->identityProperty, $messages);
        $this->assertCount(1, $messages[$this->identityProperty]);
        $this->assertArrayHasKey(Validator\EmailAddress::INVALID_FORMAT, $messages[$this->identityProperty]);
    }

    /**
     * Test register form validators with invalid email address host name
     *
     * @group forms
     * @return void
     */
    public function testRegisterFormValidatorsInvalidHostName()
    {
        $data = $this->getData();

        $data[$this->identityProperty] = 'user@domain.test';
        $this->inputFilter->setData($data);
        $this->assertFalse($this->inputFilter->isValid());
        $messages = $this->inputFilter->getMessages();
        $this->assertIsArray($messages);
        $this->assertCount(1, $messages);
        $this->assertArrayHasKey($this->identityProperty, $messages);
        $this->assertCount(3, $messages[$this->identityProperty]);
        $this->assertArrayHasKey(Validator\EmailAddress::INVALID_HOSTNAME, $messages[$this->identityProperty]);
        $this->assertArrayHasKey(Validator\Hostname::UNKNOWN_TLD, $messages[$this->identityProperty]);
        $this->assertArrayHasKey(Validator\Hostname::LOCAL_NAME_NOT_ALLOWED, $messages[$this->identityProperty]);
    }

    /**
     * Test register form validators with no password
     *
     * @group forms
     * @return void
     */
    public function testRegisterFormValidatorsEmailExists()
    {
        $this->repositoryMock
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['emailAddress' => $this->testEmail])
            ->willReturn(new BaseUser());

        $data = $this->getData();

        $this->inputFilter->setData($data);
        $this->assertFalse($this->inputFilter->isValid());
        $messages = $this->inputFilter->getMessages();
        $this->assertIsArray($messages);
        $this->assertCount(1, $messages);
        $this->assertArrayHasKey($this->identityProperty, $messages);
        $this->assertCount(1, $messages[$this->identityProperty]);
        $this->assertArrayHasKey(NoObjectExists::ERROR_OBJECT_FOUND, $messages[$this->identityProperty]);
    }

    /**
     * Test register form validators with no password
     *
     * @group forms
     * @return void
     */
    public function testRegisterFormValidatorsNoPassword()
    {
        $this->repositoryMock
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['emailAddress' => $this->testEmail])
            ->willReturn(false);

        $data = $this->getData();

        unset($data[$this->credentialProperty]);
        $this->inputFilter->setData($data);
        $this->assertFalse($this->inputFilter->isValid());
        $messages = $this->inputFilter->getMessages();
        $this->assertIsArray($messages);
        $this->assertCount(1, $messages);
        $this->assertArrayHasKey($this->credentialProperty, $messages);
        $this->assertCount(1, $messages[$this->credentialProperty]);
        $this->assertArrayHasKey(Validator\NotEmpty::IS_EMPTY, $messages[$this->credentialProperty]);
    }

    /**
     * Test register form validators with password too short
     *
     * @group forms
     * @return void
     */
    public function testRegisterFormValidatorsPasswordTooShort()
    {
        $this->repositoryMock
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['emailAddress' => $this->testEmail])
            ->willReturn(false);

        $data = $this->getData();

        $data[$this->credentialProperty] = 'test';
        $this->inputFilter->setData($data);
        $this->assertFalse($this->inputFilter->isValid());
        $messages = $this->inputFilter->getMessages();
        $this->assertIsArray($messages);
        $this->assertCount(1, $messages);
        $this->assertArrayHasKey($this->credentialProperty, $messages);
        $this->assertCount(1, $messages[$this->credentialProperty]);
        $this->assertArrayHasKey(Validator\StringLength::TOO_SHORT, $messages[$this->credentialProperty]);
    }

    /**
     * Test register form validators with no csrf
     *
     * @group forms
     * @return void
     */
    public function testRegisterFormValidatorsNoCsrf()
    {
        $this->repositoryMock
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['emailAddress' => $this->testEmail])
            ->willReturn(false);

        $data = $this->getData();

        unset($data['csrf']);
        $this->inputFilter->setData($data);
        $this->assertFalse($this->inputFilter->isValid());
        $messages = $this->inputFilter->getMessages();
        $this->assertIsArray($messages);
        $this->assertCount(1, $messages);
        $this->assertArrayHasKey('csrf', $messages);
        $this->assertCount(1, $messages['csrf']);
        $this->assertArrayHasKey(Validator\NotEmpty::IS_EMPTY, $messages['csrf']);
    }

    /**
     * Test register form validators with invalid csrf
     *
     * @group forms
     * @return void
     */
    public function testRegisterFormValidatorsCsrfInvalid()
    {
        $this->repositoryMock
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['emailAddress' => $this->testEmail])
            ->willReturn(false);

        $data = $this->getData();

        $data['csrf'] = 'invalid';
        $this->inputFilter->setData($data);
        $this->assertFalse($this->inputFilter->isValid());
        $messages = $this->inputFilter->getMessages();
        $this->assertIsArray($messages);
        $this->assertCount(1, $messages);
        $this->assertArrayHasKey('csrf', $messages);
        $this->assertCount(1, $messages['csrf']);
        $this->assertArrayHasKey(Validator\Csrf::NOT_SAME, $messages['csrf']);
    }

    /**
     * Test register form validators with valid data
     *
     * @group forms
     * @return void
     */
    public function testRegisterFormValidatorsDataValid()
    {
        $this->repositoryMock
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['emailAddress' => $this->testEmail])
            ->willReturn(false);

        $data = $this->getData();

        $this->inputFilter->setData($data);
        $this->assertTrue($this->inputFilter->isValid());
        $messages = $this->inputFilter->getMessages();
        $this->assertIsArray($messages);
        $this->assertEmpty($messages);
    }
}
