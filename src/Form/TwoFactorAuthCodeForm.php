<?php

namespace FwsDoctrineAuth\Form;

use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Form\Element;
use Laminas\Filter;
use Laminas\Validator;

/**
 * Description of TwoFactorAuthForm
 *
 * @author Garry Childs <info@freedomwebservices.net>
 */
class TwoFactorAuthCodeForm extends Form implements InputFilterProviderInterface
{
    
    public function __construct()
    {
        parent::__construct('enter-code-form');
        $this->setAttribute('method', 'post');
    }

    public function init(): void
    {
        $this->add([
            'name' => 'code',
            'type' => Element\Text::class,
            'attributes' => [
                'size' => 6,
                'maxlength' => 6,
            ],
            'options' => [
                'label' => _('Code'),
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
                'value' => _('Submit'),
                'id' => 'submitbutton',
                'label' => _('Submit'),
            ],
        ]);
    }

    public function getInputFilterSpecification(): array
    {
        return[
            'code' => [
                'required' => true,
                'filters' => [
                    ['name' => Filter\Digits::class],
                ],
                'validators' => [
                    [
                        'name' => Validator\NotEmpty::class,
                        'break_chain_on_failure' => true,
                        'options' => [
                            'messages' => [
                                Validator\NotEmpty::IS_EMPTY => _("You must enter the security code"),
                            ],
                        ],
                    ],
                    [
                        'name' => Validator\StringLength::class,
                        'break_chain_on_failure' => true,
                        'options' => [
                            'encoding' => 'UTF-8',
                            'min' => 6,
                            'max' => 6,
                            'messages' => [
                                Validator\StringLength::INVALID => _("The security code must contain %max% digits"),
                                Validator\StringLength::TOO_LONG => _("The security code must contain %max% digits"),
                                Validator\StringLength::TOO_SHORT => _("The security code must contain %min% digits"),
                            ],
                        ],
                    ],
                    [
                        'name' => Validator\Digits::class,
                        'options' => [
                            'encoding' => 'UTF-8',
                            'min' => 11,
                            'max' => 20,
                            'messages' => [
                                Validator\Digits::INVALID => _("The security code must only contain digits"),
                                Validator\Digits::NOT_DIGITS => _("The security code must only contain digits"),
                                Validator\Digits::STRING_EMPTY => _("The security code must only contain digits"),
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

}
