<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\Form;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\NotSupported;
use DoctrineModule\Validator as DoctrineModuleValidator;
use FwsDoctrineAuth\Exception\DoctrineAuthException;
use Laminas\Filter;
use Laminas\Form\Element;
use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator;

use function _;

/**
 * EmailForm
 */
class ForgottenPasswordForm extends Form implements InputFilterProviderInterface
{
    private ?string $identityClass;
    private string $identityProperty;
    private string $identityPropertyFormElement;

    /**
     * @param array $config
     * @throws DoctrineAuthException
     */
    public function __construct(
        protected EntityManager $entityManager,
        protected array $config
    ) {
        $this->identityClass = $this->config['doctrine']['authentication']['orm_default']['identity_class'] ?? null;
        if (! $this->identityClass) {
            throw new DoctrineAuthException('identity_class not found in config');
        }
        $this->identityProperty            =
            $this->config['doctrine']['authentication']['orm_default']['identity_property'] ?? null;
        $this->identityPropertyFormElement =
            $this->config['doctrineAuth']['formElements']['identity_property_element'] ?? Element\Email::class;

        parent::__construct('reset-password');
        $this->setAttribute('method', 'POST');
    }

    /**
     * Create elements
     */
    public function init(): void
    {
        /*
         * Add form elements
         */
        $this->add([
            'name'       => $this->identityProperty,
            'type'       => $this->identityPropertyFormElement,
            'attributes' => [
                'size'      => 30,
                'maxlength' => 255,
                'autofocus' => true,
            ],
            'options'    => [
                'label'            => _('Email Address'),
                'label_attributes' => ['class' => 'required'],
            ],
        ]);

        $this->add([
            'name'    => 'csrf',
            'type'    => Element\Csrf::class,
            'options' => [
                'csrf_options' => [
                    'timeout' => 600,
                ],
            ],
        ]);

        $this->add([
            'name'       => 'submit',
            'type'       => Element\Submit::class,
            'attributes' => [
                'value' => _('Reset Password'),
            ],
        ]);
    }

    /**
     * Set form filters and validators
     *
     * @return array
     * @throws NotSupported
     */
    public function getInputFilterSpecification(): array
    {
        $filter         = new Filter\StringToLower();
        $validatorChain = new Validator\ValidatorChain();

        return [
            'emailAddress' => [
                'required'   => true,
                'filters'    => [
                    ['name' => Filter\StripTags::class],
                    ['name' => Filter\StringTrim::class],
                ],
                'validators' => [
                    [
                        'name'                   => Validator\NotEmpty::class,
                        'break_chain_on_failure' => true,
                        'options'                => [
                            'messages' => [
                                Validator\NotEmpty::IS_EMPTY => _("You must specify your email address"),
                            ],
                        ],
                    ],
                    [
                        'name'    => Validator\EmailAddress::class,
                        'options' => [
                            'deep'  => true,
                            'allow' => true,
                            'mx'    => true,
//                            'messages' => [
//                                Validator\EmailAddress::INVALID => ,
//                                Validator\EmailAddress::INVALID_FORMAT => _("Your email address is invalid"),
//                                Validator\EmailAddress::INVALID_HOSTNAME => _("Your email address is invalid"),
//                                Validator\EmailAddress::INVALID_LOCAL_PART => _("Your email address is invalid"),
//                                Validator\EmailAddress::INVALID_MX_RECORD => _("Your email address is invalid"),
//                                Validator\EmailAddress::INVALID_SEGMENT => _("Your email address is invalid"),
//                                Validator\EmailAddress::LENGTH_EXCEEDED => _("Your email address is invalid"),
//                                Validator\EmailAddress::QUOTED_STRING => _("Your email address is invalid"),
//                            ],
                            'message' => _("Your email address is invalid"),
                        ],
                    ],
                    [
                        'name'                   => DoctrineModuleValidator\ObjectExists::class,
                        'break_chain_on_failure' => true,
                        'options'                => [
                            'target_class'      => $this->identityClass,
                            'object_repository' => $this->entityManager->getRepository($this->identityClass),
                            'fields'            => ['emailAddress'],
                            'messages'          => [
                                DoctrineModuleValidator\ObjectExists::ERROR_NO_OBJECT_FOUND => _('This email address is not registered'),
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
