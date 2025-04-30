<?php

declare(strict_types=1);

namespace FwsDoctrineAuthTest\Form;

use Doctrine\ORM\EntityManager;
use FwsDoctrineAuth\Form\AbstractDefaultForm;
use FwsDoctrineAuth\Form\LoginForm;
use FwsDoctrineAuthTest\ApplicationTestTrait;
use FwsDoctrineAuthTest\EntityManagerMockTrait;
use Laminas\Form\Element;
use Laminas\Form\FormInterface;
use Laminas\InputFilter\InputFilter;
use Laminas\Stdlib\Parameters;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

#[CoversClass(LoginForm::class)]
class LoginFormTest extends TestCase
{
    use ApplicationTestTrait;
    use EntityManagerMockTrait;

    private LoginForm $form;
    private InputFilter $inputFilter;
    private string|null $identityProperty;
    private string|null $credentialProperty;
    private string|null $csrfCode;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function setUp(): void
    {
        parent::setUp();

        $container = $this->getApplicationServiceLocator();
        $container->setService(EntityManager::class, $this->getEntityManagerMock());

        $this->form = $container->get('FormElementManager')->get(LoginForm::class);

        $this->inputFilter = $this->form->getInputFilter();

        $this->identityProperty = $this->getConfig()['doctrine']['authentication']['orm_default']['identity_property'] ?? null;
        $this->assertIsString($this->identityProperty);
        $this->assertNotEmpty($this->identityProperty);

        $this->credentialProperty = $this->getConfig()['doctrine']['authentication']['orm_default']['credential_property'] ?? null;
        $this->assertIsString($this->credentialProperty);
        $this->assertNotEmpty($this->credentialProperty);

        $this->csrfCode = $this->form->get('csrf')->getCsrfValidator()->getHash();
    }

    /**
     * Get test data
     */
    private function getData(): Parameters
    {
        return new Parameters([
            $this->identityProperty   => 'test@example.com',
            $this->credentialProperty => 'password',
            'csrf'                    => $this->csrfCode,
            'submit'                  => 'submit',
        ]);
    }

    /**
     * Test login form elements are set
     *
     * @group forms
     * @return void
     */
    public function testLoginFormElements()
    {
        $this->assertInstanceOf(LoginForm::class, $this->form);
        $this->assertInstanceOf(AbstractDefaultForm::class, $this->form);
        $this->assertInstanceOf(FormInterface::class, $this->form);

        /* Email address form element */
        $this->assertTrue($this->form->has($this->identityProperty));
        $this->assertInstanceOf(Element\Email::class, $this->form->get($this->identityProperty));

        /* Password form element */
        $this->assertTrue($this->form->has($this->credentialProperty));
        $this->assertInstanceOf(Element\Password::class, $this->form->get($this->credentialProperty));

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
    public function testLoginFormAttributes()
    {
        $this->assertEquals('POST', $this->form->getAttribute('method'));
        $this->assertEquals('auth', $this->form->getName());
    }

    /**
     * Test login form validators with empty email
     *
     * @group forms
     * @return void
     */
    public function testLoginFormValidatorsNoEmail()
    {
        $data = $this->getData();

        unset($data[$this->identityProperty]);
        $this->inputFilter->setData($data);
        $this->assertFalse($this->inputFilter->isValid($data));
    }

    /**
     * Test login form validators with invalid email address
     *
     * @group forms
     * @return void
     */
    public function testLoginFormValidatorsInvalidEmail()
    {
        $data = $this->getData();

        $data[$this->identityProperty] = ['10000001'];
        $this->inputFilter->setData($data);
        $this->assertFalse($this->inputFilter->isValid());
    }

    /**
     * Test login form validators with invalid format email address
     *
     * @group forms
     * @return void
     */
    public function testLoginFormValidatorsInvalidEmailFormat()
    {
        $data = $this->getData();

        $data[$this->identityProperty] = 'invalid';
        $this->inputFilter->setData($data);
        $this->assertFalse($this->inputFilter->isValid());
    }

    /**
     * Test login form validators with invalid email address host name
     *
     * @group forms
     * @return void
     */
    public function testLoginFormValidatorsInvalidHostName()
    {
        $data = $this->getData();

        $data[$this->identityProperty] = 'user@domain.test';
        $this->inputFilter->setData($data);
        $this->assertFalse($this->inputFilter->isValid());
    }

    /**
     * Test login form validators with no password
     *
     * @group forms
     * @return void
     */
    public function testLoginFormValidatorsNoPassword()
    {
        $data = $this->getData();

        unset($data[$this->credentialProperty]);
        $this->inputFilter->setData($data);
        $this->assertFalse($this->inputFilter->isValid());
    }

    /**
     * Test login form validators with password too short
     *
     * @group forms
     * @return void
     */
    public function testLoginFormValidatorsPasswordTooShort()
    {
        $data = $this->getData();

        $data[$this->credentialProperty] = 'test';
        $this->inputFilter->setData($data);
        $this->assertFalse($this->inputFilter->isValid());
    }

    /**
     * Test login form validators with no csrf
     *
     * @group forms
     * @return void
     */
    public function testLoginFormValidatorsNoCsrf()
    {
        $data = $this->getData();

        unset($data['csrf']);
        $this->inputFilter->setData($data);
        $this->assertFalse($this->inputFilter->isValid());
    }

    /**
     * Test login form validators with invalid csrf
     *
     * @group forms
     * @return void
     */
    public function testLoginFormValidatorsCsrfInvalid()
    {
        $data = $this->getData();

        $data['csrf'] = 'invalid';
        $this->inputFilter->setData($data);
        $this->assertFalse($this->inputFilter->isValid());
    }

    /**
     * Test login form validators with valid data
     *
     * @group forms
     * @return void
     */
    public function testLoginFormValidatorsDataValid()
    {
        $data = $this->getData();

        $this->inputFilter->setData($data);
        $this->assertTrue($this->inputFilter->isValid());
    }
}
