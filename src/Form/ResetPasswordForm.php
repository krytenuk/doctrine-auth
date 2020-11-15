<?php

namespace FwsDoctrineAuth\Form;

use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Form\Element;
use Laminas\Validator;
use FwsDoctrineAuth\Exception\DoctrineAuthException;

/**
 * ForgotPasswordForm
 *
 * @author Garry Childs <info@freedomwebservices.net>
 */
class ResetPasswordForm extends Form implements InputFilterProviderInterface
{

    /**
     *
     * @var array
     */
    protected $config;

    /**
     * 
     * @param array $config
     */
    public function __construct(Array $config)
    {
        parent::__construct('reset-password');
        $this->config = $config;
    }

    public function init(): void
    {
        if (!isset($this->config['doctrineAuth']['formElements']['identity_label']) || !isset($this->config['doctrineAuth']['formElements']['credential_label'])) {
            throw new DoctrineAuthException('identity_label and/or credential_label not found in config');
        }

        $this->add([
            'name' => $this->getCredentialName(),
            'type' => Element\Password::class,
            'attributes' => [
                'size' => 16,
                'maxlength' => 16,
            ],
            'options' => [
                'label' => sprintf(_('New %s'), $this->config['doctrineAuth']['formElements']['credential_label']),
                'label_attributes' => ['class' => 'required'],
            ],
        ]);

        $this->add([
            'name' => $this->getRetypeCredentialName(),
            'type' => Element\Password::class,
            'attributes' => [
                'size' => 16,
                'maxlength' => 16,
            ],
            'options' => [
                'label' => sprintf(_('Retype %s'), $this->config['doctrineAuth']['formElements']['credential_label']),
                'label_attributes' => ['class' => 'required'],
            ],
        ]);

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
                'value' => _('Set Password'),
            ],
        ]);

        $this->setValidationGroup(array(
            $this->config['doctrine']['authentication']['orm_default']['credential_property'],
            sprintf('retype', ucfirst($this->config['doctrine']['authentication']['orm_default']['credential_property'])),
            'csrf',
        ));
    }

    public function getInputFilterSpecification()
    {
        return [
            $this->getCredentialName() => [
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
            $this->getRetypeCredentialName() => [
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
                    [
                        'name' => Validator\Identical::class,
                        'options' => [
                            'token' => $this->config['doctrine']['authentication']['orm_default']['credential_property'],
                            'messages' => [
                                Validator\Identical::MISSING_TOKEN => _("The passwords don't match"),
                                Validator\Identical::NOT_SAME => _("The passwords don't match"),
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    public function getCredentialName()
    {
        return $this->config['doctrine']['authentication']['orm_default']['credential_property'];
    }

    public function getRetypeCredentialName()
    {
        return sprintf('retype', ucfirst($this->config['doctrine']['authentication']['orm_default']['credential_property']));
    }

}
