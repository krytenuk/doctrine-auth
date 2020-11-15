<?php

namespace FwsDoctrineAuth\Form;

use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Form\Element;
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
     * @param array $config
     */
    public function __construct(EntityManager $entityManager, Array $config)
    {
        parent::__construct('reset-password');
        $this->config = $config;
        $this->entityManager = $entityManager;
    }

    public function init(): void
    {
        if (!isset($this->config['doctrine']['authentication']['orm_default']['identity_property']) || !isset($this->config['doctrine']['authentication']['orm_default']['credential_property'])) {
            throw new DoctrineAuthException('identity_property and/or credential_property not found in config');
        }

        $this->add([
            'name' => $this->getIdentityName(),
            'type' => Element\Text::class,
            'attributes' => [
                'size' => 30,
                'maxlength' => 255,
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

    public function getInputFilterSpecification(): array
    {
        return [
            $this->getIdentityName() => [
                'required' => TRUE,
                'filters' => [
                    ['name' => 'StripTags'],
                    ['name' => 'StringTrim'],
                ],
                'validators' => [
                    [
                        'name' => DoctrineModuleValidator\ObjectExists::class,
                        'break_chain_on_failure' => TRUE,
                        'options' => [
                            'target_class' => $this->config['doctrine']['authentication']['orm_default']['identity_class'],
                            'object_repository' => $this->entityManager->getRepository($this->config['doctrine']['authentication']['orm_default']['identity_class']),
                            'fields' => [$this->config['doctrine']['authentication']['orm_default']['identity_property']],
                        ],
                    ],
                ]
            ],
        ];
    }

    public function getIdentityName()
    {
        return $this->config['doctrine']['authentication']['orm_default']['identity_property'];
    }

}
