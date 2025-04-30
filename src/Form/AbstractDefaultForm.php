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
    protected ?string $identityClass               = null;
    protected ?string $identityProperty            = null;
    protected ?string $credentialProperty          = null;
    protected ?string $identityLabel               = null;
    protected ?string $credentialLabel             = null;
    protected ?string $identityPropertyFormElement = null;

    /**
     * @param array $config
     * @throws DoctrineAuthException
     */
    public function __construct(
        protected EntityManager $entityManager,
        protected array $config
    ) {
        /* Identity/credential property not found in config */
        $this->identityProperty   =
            $this->config['doctrine']['authentication']['orm_default']['identity_property'] ?? null;
        $this->credentialProperty =
            $this->config['doctrine']['authentication']['orm_default']['credential_property'] ?? null;
        if (! ($this->identityProperty && $this->credentialProperty)) {
            throw new DoctrineAuthException('identity_property and/or credential_property not found in config');
        }
        /* Identity/credential label not found in config */
        $this->identityLabel   = $this->config['doctrineAuth']['formElements']['identity_label'];
        $this->credentialLabel = $this->config['doctrineAuth']['formElements']['credential_label'];
        if (! ($this->identityLabel && $this->credentialLabel)) {
            throw new DoctrineAuthException('identity_label and/or credential_label not found in config');
        }
        $this->identityPropertyFormElement =
            $this->config['doctrineAuth']['formElements']['identity_property_element'] ?? Element\Email::class;

        parent::__construct('auth');
        $this->setAttribute('method', 'POST');
    }

    /**
     * @todo Document identity and credential getters
     */
    public function getIdentityProperty(): ?string
    {
        return $this->identityProperty;
    }

    /**
     * @todo Document identity and credential getters
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
                    'deep'    => true,
                    'allow'   => true,
                    'mx'      => true,
                    'message' => _("Your email address is invalid"),
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
        /**
         * @todo Remove, just use init() method override instead
         */
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
    protected function generateValidationGroup(FieldsetInterface $formOrFieldset): array
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
        /* Identity class not found in config */
        $this->identityClass = $this->config['doctrine']['authentication']['orm_default']['identity_class'] ?? null;
        if (! $this->identityClass) {
            throw new DoctrineAuthException('identity_class not found in config');
        }

        $filter = [];

        /* Add custom user filters and validators if exists */
        /**
         * @todo Remove, just use getInputFilterSpecification() method override instead
         */
        if (method_exists($this, 'addInputFilterSpecification')) {
            $filter = $this->addInputFilterSpecification();
        }

        /* Return input filters and validators */
        $filtersArray = array_merge([
            $this->identityProperty   => [
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
                            'message' => _("You must specify your email address"),
                        ],
                    ],
                ],
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
                            'message' => _("You must specify your password"),
                        ],
                    ],
                    [
                        'name'    => Validator\StringLength::class,
                        'options' => [
                            'encoding' => 'UTF-8',
                            'min'      => 8,
                            'max'      => 16,
                            'message'  => _("Your password must contain between %min% and %max% characters"),
                        ],
                    ],
                ],
            ],
        ], $filter);

        /* Register form */
        if ($this instanceof RegisterForm) {
            $filtersArray[$this->identityProperty]['validators'][] = [
                'name'                   => DoctrineModuleValidator\NoObjectExists::class,
                'break_chain_on_failure' => true,
                'options'                => [
                    'target_class'      => $this->identityClass,
                    'object_repository' => $this->entityManager->getRepository($this->identityClass),
                    'fields'            => [$this->identityProperty],
                    'message'           => _("This email address is already registered"),
                ],
            ];
        }

        return $filtersArray;
    }
}
