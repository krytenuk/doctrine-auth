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
                'autofocus' => true,
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
                    ['name' => Filter\ToInt::class],
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
                        'name' => Validator\Digits::class,
                        'break_chain_on_failure' => true,
                        'options' => [
                            'messages' => [
                                Validator\Digits::INVALID => _("The security code must only contain digits"),
                                Validator\Digits::NOT_DIGITS => _("The security code must only contain digits"),
                                Validator\Digits::STRING_EMPTY => _("You must enter the security code"),
                            ],
                        ],
                    ],
                    [
                        'name' => Validator\Between::class,
                        'break_chain_on_failure' => true,
                        'options' => [
                            'encoding' => 'UTF-8',
                            'min' => 1,
                            'max' => 999999,
                            'messages' => [
                                Validator\Between::NOT_BETWEEN => _("The security code must contain 6 digits"),
                                Validator\Between::NOT_BETWEEN_STRICT => _("The security code must contain 6 digits"),
                                Validator\Between::VALUE_NOT_NUMERIC => _("The security code is invalid"),
                                Validator\Between::VALUE_NOT_STRING => _("The security code is invalid"),
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

}
