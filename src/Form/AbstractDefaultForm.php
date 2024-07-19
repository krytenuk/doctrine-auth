<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\Form;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\NotSupported;
use DoctrineModule\Validator as DoctrineModuleValidator;
use FwsDoctrineAuth\Exception\DoctrineAuthException;
use Laminas\Filter;
use Laminas\Form\Element;
use Laminas\Form\Fieldset;
use Laminas\Form\FieldsetInterface;
use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator;

use function _;
use function array_merge;
use function method_exists;

/**
 * DefaultForm
 */
abstract class AbstractDefaultForm extends Form implements InputFilterProviderInterface
{
    protected ?string $identityProperty            = null;
    protected ?string $credentialProperty          = null;
    protected ?string $identityLabel               = null;
    protected ?string $credentialLabel             = null;
    protected ?string $identityPropertyFormElement = null;

    /**
     * @param EntityManager $entityManager
     * @param array $config
     * @throws DoctrineAuthException
     */
    public function __construct(
        protected EntityManager $entityManager,
        protected array $config
    ) {
        /* Identity/credential property not found in config */
        $this->identityProperty   = $this->config['doctrine']['authentication']['orm_default']['identity_property'] ?? null;
        $this->credentialProperty = $this->config['doctrine']['authentication']['orm_default']['credential_property'] ?? null;
        if (! ($this->identityProperty && $this->credentialProperty)) {
            throw new DoctrineAuthException('identity_property and/or credential_property not found in config');
        }
        /* Identity/credential label not found in config */
        $this->identityLabel   = $this->config['doctrineAuth']['formElements']['identity_label'];
        $this->credentialLabel = $this->config['doctrineAuth']['formElements']['credential_label'];
        if (! ($this->identityLabel && $this->credentialLabel)) {
            throw new DoctrineAuthException('identity_label and/or credential_label not found in config');
        }
        $this->identityPropertyFormElement = $this->config['doctrineAuth']['formElements']['identity_property_element'] ?? Element\Email::class;

        parent::__construct('auth');
        $this->setAttribute('method', 'POST');
    }

    /**
     * @todo Document identity and credential getters
     * @return string|null
     */
    public function getIdentityProperty(): ?string
    {
        return $this->identityProperty;
    }

    /**
     * @todo Document identity and credential getters
     * @return string|null
     */
    public function getCredentialProperty(): ?string
    {
        return $this->credentialProperty;
    }

    /**
     * Create form elements
     */
    public function init(): void
    {
        $this->add([
            'name'       => $this->identityProperty,
            'type'       => $this->identityPropertyFormElement,
            'attributes' => [
                'size'      => 16,
                'maxlength' => 255,
                'autofocus' => true,
            ],
            'options'    => [
                'label'            => _($this->identityLabel),
                'label_attributes' => ['class' => 'required'],
            ],
        ]);
        $identityElement = $this->get($this->identityProperty);
        if ($identityElement instanceof Element\Email) {
            $identityElement->setEmailValidator(new Validator\EmailAddress([
                'options' => [
                    'deep'     => true,
                    'allow'    => true,
                    'mx'       => true,
                    'messages' => [
                        Validator\EmailAddress::INVALID            => _("Your email address is invalid"),
                        Validator\EmailAddress::INVALID_FORMAT     => _("Your email address is invalid"),
                        Validator\EmailAddress::INVALID_HOSTNAME   => _("Your email address is invalid"),
                        Validator\EmailAddress::INVALID_LOCAL_PART => _("Your email address is invalid"),
                        Validator\EmailAddress::INVALID_MX_RECORD  => _("Your email address is invalid"),
                        Validator\EmailAddress::INVALID_SEGMENT    => _("Your email address is invalid"),
                        Validator\EmailAddress::LENGTH_EXCEEDED    => _("Your email address is invalid"),
                        Validator\EmailAddress::QUOTED_STRING      => _("Your email address is invalid"),
                    ],
                ],
            ]));
        }

        $this->add([
            'name'       => $this->credentialProperty,
            'type'       => Element\Password::class,
            'attributes' => [
                'size'      => 16,
                'maxlength' => 16,
            ],
            'options'    => [
                'label'            => _($this->credentialLabel),
                'label_attributes' => ['class' => 'required'],
            ],
        ]);

        /* Add custom user elements */
        if (method_exists($this, 'addElements')) {
            $this->addElements();
        }

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
                'value' => _('Submit'),
                'label' => _('Submit'),
            ],
        ]);

        $this->setValidationGroup($this->generateValidationGroup($this));
    }

    /**
     * Get form or fieldset element names as an array for use in @param FieldsetInterface $formOrFieldset
     *
     * @see Form::setValidationGroup()
     *
     * @return array
     */
    private function generateValidationGroup(FieldsetInterface $formOrFieldset): array
    {
        $validationGroup = [];
        foreach ($formOrFieldset as $element) {
            if ($element instanceof Fieldset) {
                $validationGroup[$element->getName()] = $this->getValidationGroup();
            } else {
                $validationGroup[] = $element->getName();
            }
        }
        return $validationGroup;
    }

    /**
     * Set form filters and validators
     *
     * @return array
     * @throws DoctrineAuthException|NotSupported
     */
    public function getInputFilterSpecification(): array
    {
        /* Default identity validators */
        $validatorChain = new Validator\ValidatorChain();
        $validatorChain->attach(new Validator\NotEmpty([
            'break_chain_on_failure' => true,
            'messages'               => [
                Validator\NotEmpty::IS_EMPTY => _("You must specify your email address"),
            ],
        ]));

        /* Register form */
        if ($this instanceof RegisterFormAbstract) {
            /* Identity class not found in config */
            $identityClass = $this->config['doctrine']['authentication']['orm_default']['identity_class'] ?? null;
            if (! $identityClass) {
                throw new DoctrineAuthException('identity_class not found in config');
            }
            /* Add no object exists validator to identity validators */
            $validatorChain->attach(new DoctrineModuleValidator\NoObjectExists([
                'break_chain_on_failure' => true,
                'target_class'           => $identityClass,
                'object_repository'      => $this->entityManager->getRepository($identityClass),
                'fields'                 => [$this->identityProperty],
                'messages'               => [
                    DoctrineModuleValidator\NoObjectExists::ERROR_OBJECT_FOUND => _("This email address is already registered"),
                ],
            ]));
        }

        $filter = [];

        /* Add custom user filters and validators if exists */
        if (method_exists($this, 'addInputFilterSpecification')) {
            $filter = $this->addInputFilterSpecification();
        }

        /* Return input filters and validators */
        return array_merge($filter, [
            $this->identityProperty   => [
                'required'   => true,
                'filters'    => [
                    ['name' => Filter\StripTags::class],
                    ['name' => Filter\StringTrim::class],
                ],
                'validators' => $validatorChain,
            ],
            $this->credentialProperty => [
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
                                Validator\NotEmpty::IS_EMPTY => _("You must specify your password"),
                            ],
                        ],
                    ],
                    [
                        'name'    => Validator\StringLength::class,
                        'options' => [
                            'encoding' => 'UTF-8',
                            'min'      => 8,
                            'max'      => 16,
                            'messages' => [
                                Validator\StringLength::INVALID   => _("Your password must contain between %min% and %max% characters"),
                                Validator\StringLength::TOO_LONG  => _("Your password must not contain more than %max% characters"),
                                Validator\StringLength::TOO_SHORT => _("Your password must contain more than %min% characters"),
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }
}
