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

    /**
     *
     * @param EntityManager $entityManager
     * @param array $config
     * @throws DoctrineAuthException
     */
    public function __construct(
        protected EntityManager $entityManager,
        protected array         $config
    )
    {
        $this->identityClass = $this->config['doctrine']['authentication']['orm_default']['identity_class'] ?? null;
        if (!$this->identityClass) {
            throw new DoctrineAuthException('identity_class not found in config');
        }

        parent::__construct('reset-password');
        $this->setAttribute('method', 'POST');
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
            'name' => 'emailAddress',
            'type' => Element\Email::class,
            'attributes' => [
                'size' => 30,
                'maxlength' => 255,
                'autofocus' => true,
            ],
            'options' => [
                'label' => _('Email Address'),
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
        $validatorChain = new Validator\ValidatorChain();

        return [
            'emailAddress' => [
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
                                Validator\NotEmpty::IS_EMPTY => _("You must specify your email address"),
                            ],
                        ],
                    ],
                    [
                        'name' => Validator\EmailAddress::class,
                        'options' => [
                            'deep' => true,
                            'allow' => true,
                            'mx' => true,
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
                        'name' => DoctrineModuleValidator\ObjectExists::class,
                        'break_chain_on_failure' => TRUE,
                        'options' => [
                            'target_class' => $this->identityClass,
                            'object_repository' => $this->entityManager->getRepository($this->identityClass),
                            'fields' => ['emailAddress'],
                            'messages' => [
                                DoctrineModuleValidator\ObjectExists::ERROR_NO_OBJECT_FOUND => _('This email address is not registered'),
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
