<?php

namespace FwsDoctrineAuth\Form;

use Doctrine\ORM\Exception\NotSupported;
use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Form\Element;
use Laminas\Filter;
use Laminas\Validator;
use DoctrineModule\Validator as DoctrineModuleValidator;
use FwsDoctrineAuth\Exception\DoctrineAuthException;
use Doctrine\ORM\EntityManager;

/**
 * EmailForm
 *
 * @author Garry Childs <info@freedomwebservices.net>
 */
class ForgottenPasswordForm extends Form implements InputFilterProviderInterface
{
    private ?string $identityClass;
    private ?string $identityProperty;
    private ?string $identityLabel;

    /**
     *
     * @param EntityManager $entityManager
     * @param array $config
     * @throws DoctrineAuthException
     */
    public function __construct(
        protected EntityManager $entityManager,
        protected array $config
    )
    {
        $this->identityLabel = $this->config['doctrineAuth']['formElements']['identity_label'] ?? null;
        if (!$this->identityLabel) {
            throw new DoctrineAuthException('identity_label not found in config');
        }

        $this->identityClass = $this->config['doctrine']['authentication']['orm_default']['identity_class'] ?? null;
        if (!$this->identityClass) {
            throw new DoctrineAuthException('identity_class not found in config');
        }

        $this->identityProperty = $this->config['doctrine']['authentication']['orm_default']['identity_property'] ?? null;
        if (!$this->identityProperty) {
            throw new DoctrineAuthException('identity_property not found in config');
        }

        parent::__construct('reset-password');
        $this->setAttribute('method', 'post');
    }

    /**
     * Create elements
     * @return void
     */
    public function init(): void
    {

        /*
         * Add form elements
         */

        $this->add([
            'name' => $this->getIdentityName(),
            'type' => Element\Text::class,
            'attributes' => [
                'size' => 30,
                'maxlength' => 255,
                'autofocus' => true,
            ],
            'options' => [
                'label' => _($this->identityLabel),
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
                'value' => _('Reset Password'),
            ],
        ]);
    }

    /**
     * Set form filters and validators
     * @return array
     * @throws NotSupported
     */
    public function getInputFilterSpecification(): array
    {
        $filter = new Filter\StringToLower();
        return [
            $this->getIdentityName() => [
                'required' => TRUE,
                'filters' => [
                    ['name' => Filter\StripTags::class],
                    ['name' => Filter\StringTrim::class],
                ],
                'validators' => [
                    [
                        'name' => Validator\NotEmpty::class,
                        'break_chain_on_failure' => TRUE,
                        'options' => [
                            'messages' => [
                                Validator\NotEmpty::IS_EMPTY => sprintf(_("You must specify your %s"), $filter->filter($this->identityLabel)),
                            ],
                        ],
                    ],
                    [
                        'name' => DoctrineModuleValidator\ObjectExists::class,
                        'break_chain_on_failure' => TRUE,
                        'options' => [
                            'target_class' => $this->identityClass,
                            'object_repository' => $this->entityManager->getRepository($this->identityClass),
                            'fields' => [$this->getIdentityName()],
                            'messages' => [
                                DoctrineModuleValidator\ObjectExists::ERROR_NO_OBJECT_FOUND => sprintf(_('This %s is not registered'), $filter->filter($this->identityLabel)),
                            ],
                        ],
                    ],
                ]
            ],
        ];
    }

    /**
     * Get identity property name
     * @return string|null
     */
    public function getIdentityName(): ?string
    {
        return $this->identityProperty;
    }

}
