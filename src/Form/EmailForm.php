<?php

namespace FwsDoctrineAuth\Form;

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
class EmailForm extends Form implements InputFilterProviderInterface
{

    /**
     *
     * @var array
     */
    protected $config;

    /**
     *
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * 
     * @param EntityManager $entityManager
     * @param array $config
     */
    public function __construct(EntityManager $entityManager, Array $config)
    {
        parent::__construct('reset-password');
        $this->config = $config;
        $this->entityManager = $entityManager;
        $this->setAttribute('method', 'post');
    }

    /**
     * Create elements
     * @return void
     * @throws DoctrineAuthException
     */
    public function init(): void
    {
        if (!isset($this->config['doctrineAuth']['formElements']['identity_label'])) {
            throw new DoctrineAuthException('identity_label not found in config');
        }

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
                'label' => _($this->config['doctrineAuth']['formElements']['identity_label']),
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
                                Validator\NotEmpty::IS_EMPTY => sprintf(_("You must specify your %s"), $filter->filter($this->config['doctrineAuth']['formElements']['identity_label'])),
                            ],
                        ],
                    ],
                    [
                        'name' => DoctrineModuleValidator\ObjectExists::class,
                        'break_chain_on_failure' => TRUE,
                        'options' => [
                            'target_class' => $this->config['doctrine']['authentication']['orm_default']['identity_class'],
                            'object_repository' => $this->entityManager->getRepository($this->config['doctrine']['authentication']['orm_default']['identity_class']),
                            'fields' => [$this->getIdentityName()],
                            'messages' => [
                                DoctrineModuleValidator\ObjectExists::ERROR_NO_OBJECT_FOUND => sprintf(_('This %s is not registered'), $filter->filter($this->config['doctrineAuth']['formElements']['identity_label'])),
                            ],
                        ],
                    ],
                ]
            ],
        ];
    }

    /**
     * 
     * @return string
     */
    public function getIdentityName(): string
    {
        if (!isset($this->config['doctrine']['authentication']['orm_default']['identity_property'])) {
            throw new DoctrineAuthException('identity_property not found in config');
        }
        
        return $this->config['doctrine']['authentication']['orm_default']['identity_property'];
    }

}
