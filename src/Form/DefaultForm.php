<?php

namespace FwsDoctrineAuth\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilterProviderInterface;
use Zend\Form\Element;
use Zend\Form\Fieldset;
use Zend\Validator;
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
    private $config;

    /**
     *
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * 
     * @param array $config
     */
    public function __construct(EntityManager $objectManager, Array $config)
    {
        parent::__construct('auth');
        $this->objectManager = $objectManager;
        $this->config = $config;
    }

    public function init()
    {
        if (!isset($this->config['doctrine']['authentication']['orm_default']['identity_property']) || !isset($this->config['doctrine']['authentication']['orm_default']['credential_property'])) {
            throw new DoctrineAuthException('identity_property and/or credential_property not found in config');
        }

        if (!isset($this->config['doctrineAuth']['formElements']['identity_label']) || !isset($this->config['doctrineAuth']['formElements']['credential_label'])) {
            throw new DoctrineAuthException('identity_label and/or credential_label not found in config');
        }

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
        
        $validationGroup = [];
        foreach ($this as $element) {
            if ($element instanceof Fieldset) {
                $validationGroup[$element->getName()] = $this->addFieldsetToValidationGroup($element);
            } else {
               $validationGroup[] = $element->getName(); 
            }
        }
        
        $this->setValidationGroup($validationGroup);
    }
    
    private function addFieldsetToValidationGroup(Fieldset $fieldset)
    {
        $array = [];
        foreach ($fieldset as $element) {
            $array[] = $element->getName();
        }
        return $array;
    }


//    public function setValidationGroup(Array $validationGroup = NULL)
//    {
//        $validationGroup[] = $this->config['doctrine']['authentication']['orm_default']['identity_property'];
//        $validationGroup[] = $this->config['doctrine']['authentication']['orm_default']['credential_property'];
//        $validationGroup[] = 'csrf';
//        
//        parent::setValidationGroup($validationGroup);
//    }

    public function getInputFilterSpecification()
    {
        $validators = [
            [
                'name' => Validator\NotEmpty::class,
                'break_chain_on_failure' => TRUE,
                'options' => [
                    'messages' => [
                        Validator\NotEmpty::IS_EMPTY => _("You must specify your email address"),
                    ],
                ],
            ],
            [
                'name' => Validator\StringLength::class,
                'break_chain_on_failure' => TRUE,
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
                    'deep' => TRUE,
                    'allow' => TRUE,
                    'mx' => TRUE,
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

        if ($this instanceof RegisterForm) {
            if (!isset($this->config['doctrine']['authentication']['orm_default']['identity_class'])) {
                throw new DoctrineAuthException('identity_class not found in config');
            }
            $validators[] = [
                'name' => DoctrineModuleValidator\NoObjectExists::class,
                'break_chain_on_failure' => TRUE,
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
        
        if (method_exists($this, 'addInputFilterSpecification')) {
            $filter = $this->addInputFilterSpecification();
        }

        return array_merge($filter, [
            $this->config['doctrine']['authentication']['orm_default']['identity_property'] => [
                'required' => TRUE,
                'filters' => [
                    ['name' => 'StripTags'],
                    ['name' => 'StringTrim'],
                ],
                'validators' => $validators
            ],
            $this->config['doctrine']['authentication']['orm_default']['credential_property'] => [
                'required' => TRUE,
                'filters' => [
                    ['name' => 'StripTags'],
                    ['name' => 'StringTrim'],
                ],
                'validators' => [
                    [
                        'name' => Validator\NotEmpty::class,
                        'break_chain_on_failure' => TRUE,
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
