<?php

namespace FwsDoctrineAuth\Form;

use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Form\Element;
use Laminas\Form\Fieldset;
use Laminas\Form\FieldsetInterface;
use Laminas\Filter;
use Laminas\Validator;
use DoctrineModule\Validator as DoctrineModuleValidator;
use FwsDoctrineAuth\Exception\DoctrineAuthException;
use Doctrine\ORM\EntityManager;

/**
 * DefaultForm
 *
 * @author Garry Childs <info@freedomwebservices.net>
 */
abstract class DefaultForm extends Form implements InputFilterProviderInterface
{

    /**
     *
     * @var array
     */
    protected $config;

    /**
     *
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * 
     * @param EntityManager $objectManager
     * @param array $config
     */
    public function __construct(EntityManager $objectManager, array $config)
    {
        parent::__construct('auth');
        $this->objectManager = $objectManager;
        $this->config = $config;
        $this->setAttribute('method', 'post');
    }

    /**
     * Create form elements
     * @return void
     * @throws DoctrineAuthException
     */
    public function init(): void
    {
        /* Identity property not found in config */
        if (isset($this->config['doctrine']['authentication']['orm_default']['identity_property']) === false || isset($this->config['doctrine']['authentication']['orm_default']['credential_property']) === false) {
            throw new DoctrineAuthException('identity_property and/or credential_property not found in config');
        }

        /* Credential property not found in config */
        if (isset($this->config['doctrineAuth']['formElements']['identity_label']) === false || isset($this->config['doctrineAuth']['formElements']['credential_label']) === false) {
            throw new DoctrineAuthException('identity_label and/or credential_label not found in config');
        }
                
        /*
         * Add form elements
         */
        $this->add([
            'name' => $this->config['doctrine']['authentication']['orm_default']['identity_property'],
            'type' => Element\Text::class,
            'attributes' => [
                'size' => 16,
                'maxlength' => 255,
            ],
            'options' => [
                'label' => _($this->config['doctrineAuth']['formElements']['identity_label']),
                'label_attributes' => ['class' => 'required'],
            ],
        ]);

        $this->add([
            'name' => $this->config['doctrine']['authentication']['orm_default']['credential_property'],
            'type' => Element\Password::class,
            'attributes' => [
                'size' => 16,
                'maxlength' => 16,
            ],
            'options' => [
                'label' => _($this->config['doctrineAuth']['formElements']['credential_label']),
                'label_attributes' => ['class' => 'required'],
            ],
        ]);

        /* Add custom user elements */
        if (method_exists($this, 'addElements')) {
            $this->addElements();
        }

        $this->add([
            'name' => 'csrf',
            'type' => Element\Csrf::class,
            'options' => [
                'csrf_options' => [
                    'timeout' => 600,
                ],
            ],
        ]);

        $this->add([
            'name' => 'submit',
            'type' => Element\Submit::class,
            'attributes' => [
                'value' => _('Submit'),
                'id' => 'submitbutton',
                'label' => _('Submit'),
            ],
        ]);

        $this->setValidationGroup($this->generateValidationGroup($this));
    }

    /**
     * Get form or fieldset element names as an array for use in @see Form::setValidationGroup()
     * @param FieldsetInterface $formOrFieldset
     * @return array
     */
    private function generateValidationGroup(FieldsetInterface $formOrFieldset): array
    {
        $validationGroup = [];
        foreach ($formOrFieldset as $element) {
            if ($element instanceof Fieldset) {
                $validationGroup[$element->getName()] = $this->getValidationGroup($element);
            } else {
                $validationGroup[] = $element->getName();
            }
        }
        return $validationGroup;
    }

    /**
     * Set form filters and validators
     * @return array
     * @throws DoctrineAuthException
     */
    public function getInputFilterSpecification(): array
    {
        /* Default identity validators */
        $validators = [
            [
                'name' => Validator\NotEmpty::class,
                'break_chain_on_failure' => true,
                'options' => [
                    'messages' => [
                        Validator\NotEmpty::IS_EMPTY => _("You must specify your email address"),
                    ],
                ],
            ],
            [
                'name' => Validator\StringLength::class,
                'break_chain_on_failure' => true,
                'options' => [
                    'encoding' => 'UTF-8',
                    'min' => 2,
                    'max' => 255,
                    'messages' => [
                        Validator\StringLength::INVALID => _("Your email address must contain between %min% and %max% characters"),
                        Validator\StringLength::TOO_LONG => _("Your email address must not contain more than %max% characters"),
                        Validator\StringLength::TOO_SHORT => _("Your email address must contain more than %min% characters"),
                    ],
                ],
            ],
            [
                'name' => Validator\EmailAddress::class,
                'options' => [
                    'deep' => true,
                    'allow' => true,
                    'mx' => true,
                    'messages' => [
                        Validator\EmailAddress::INVALID => _("Your email address is invalid"),
                        Validator\EmailAddress::INVALID_FORMAT => _("Your email address is invalid"),
                        Validator\EmailAddress::INVALID_HOSTNAME => _("Your email address is invalid"),
                        Validator\EmailAddress::INVALID_LOCAL_PART => _("Your email address is invalid"),
                        Validator\EmailAddress::INVALID_MX_RECORD => _("Your email address is invalid"),
                        Validator\EmailAddress::INVALID_SEGMENT => _("Your email address is invalid"),
                        Validator\EmailAddress::LENGTH_EXCEEDED => _("Your email address is invalid"),
                        Validator\EmailAddress::QUOTED_STRING => _("Your email address is invalid"),
                    ],
                ],
            ],
        ];

        /* Register form */
        if ($this instanceof RegisterForm === true) {
            /* Identity class not found in config */
            if (isset($this->config['doctrine']['authentication']['orm_default']['identity_class']) === false) {
                throw new DoctrineAuthException('identity_class not found in config');
            }
            /* Add no object exists validator to identity validators */
            $validators[] = [
                'name' => DoctrineModuleValidator\NoObjectExists::class,
                'break_chain_on_failure' => true,
                'options' => [
                    'target_class' => $this->config['doctrine']['authentication']['orm_default']['identity_class'],
                    'object_repository' => $this->objectManager->getRepository($this->config['doctrine']['authentication']['orm_default']['identity_class']),
                    'fields' => [$this->config['doctrine']['authentication']['orm_default']['identity_property']],
                    'messages' => [
                        DoctrineModuleValidator\NoObjectExists::ERROR_OBJECT_FOUND => _("This email address is already registered"),
                    ],
                ],
            ];
        }

        $filter = [];

        /* Add custom user filters and validators if exists */
        if (method_exists($this, 'addInputFilterSpecification') === true) {
            $filter = $this->addInputFilterSpecification();
        }

        /* Return input filters and validators */
        return array_merge($filter, [
            $this->config['doctrine']['authentication']['orm_default']['identity_property'] => [
                'required' => true,
                'filters' => [
                    ['name' => Filter\StripTags::class],
                    ['name' => Filter\StringTrim::class],
                ],
                'validators' => $validators
            ],
            $this->config['doctrine']['authentication']['orm_default']['credential_property'] => [
                'required' => true,
                'filters' => [
                    ['name' => Filter\StripTags::class],
                    ['name' => Filter\StringTrim::class],
                ],
                'validators' => [
                    [
                        'name' => Validator\NotEmpty::class,
                        'break_chain_on_failure' => true,
                        'options' => [
                            'messages' => [
                                Validator\NotEmpty::IS_EMPTY => _("You must specify your password"),
                            ],
                        ],
                    ],
                    [
                        'name' => Validator\StringLength::class,
                        'options' => [
                            'encoding' => 'UTF-8',
                            'min' => 8,
                            'max' => 16,
                            'messages' => [
                                Validator\StringLength::INVALID => _("Your password must contain between %min% and %max% characters"),
                                Validator\StringLength::TOO_LONG => _("Your password must not contain more than %max% characters"),
                                Validator\StringLength::TOO_SHORT => _("Your password must contain more than %min% characters"),
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }

}
