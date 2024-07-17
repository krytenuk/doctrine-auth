<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\Form;

use Doctrine\Laminas\Hydrator\DoctrineObject;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\NotSupported;
use FwsDoctrineAuth\Exception\DoctrineAuthException;
use Laminas\Filter;
use Laminas\Form\Element;
use Laminas\I18n\Validator as I18nValidator;
use Laminas\Validator;

use function _;
use function array_merge;

/**
 * RegisterForm
 */
class RegisterForm extends DefaultForm
{
    public function __construct(EntityManager $entityManager, array $config)
    {
        parent::__construct($entityManager, $config);
        $this->setHydrator(new DoctrineObject($entityManager));
    }

    /**
     * Create form elements
     */
    public function init(): void
    {
        $this->add([
            'name'       => 'mobileNumber',
            'type'       => Element\Text::class,
            'attributes' => [
                'size'        => 16,
                'maxlength'   => 20,
                'placeholder' => '+447000000000',
            ],
            'options'    => [
                'label' => _('Mobile Number'),
            ],
        ]);

        parent::init();

        $this->get('submit')->setValue('Register');
    }

    /**
     * Get input filter specification array
     *
     * @return array
     * @throws DoctrineAuthException|NotSupported
     */
    public function getInputFilterSpecification(): array
    {
        $stringToUpperFilter = new Filter\StringToUpper();
        $siteCountryCode     = $this->config['doctrineAuth']['siteCountryCode'] ?? null;
        if (! $siteCountryCode) {
            throw new DoctrineAuthException('siteCountryCode not found in doctrineAuth config');
        }

        return array_merge(
            parent::getInputFilterSpecification(),
            [
                'mobileNumber' => [
                    'required'   => false,
                    'filters'    => [
                        ['name' => Filter\Digits::class],
                    ],
                    'validators' => [
                        [
                            'name'                   => Validator\StringLength::class,
                            'break_chain_on_failure' => true,
                            'options'                => [
                                'encoding' => 'UTF-8',
                                'min'      => 11,
                                'max'      => 20,
                                'messages' => [
                                    Validator\StringLength::INVALID   => _("The mobile number must contain between %min% and %max% digits"),
                                    Validator\StringLength::TOO_LONG  => _("The mobile number must not contain more than %max% digits"),
                                    Validator\StringLength::TOO_SHORT => _("The mobile number must contain %min% or more digits"),
                                ],
                            ],
                        ],
                        [
                            'name'                   => Validator\Digits::class,
                            'break_chain_on_failure' => true,
                            'options'                => [
                                'encoding' => 'UTF-8',
                                'min'      => 11,
                                'max'      => 20,
                                'messages' => [
                                    Validator\Digits::INVALID      => _("The mobile number must only contain digits"),
                                    Validator\Digits::NOT_DIGITS   => _("The mobile number must only contain digits"),
                                    Validator\Digits::STRING_EMPTY => _("The mobile number must only contain digits"),
                                ],
                            ],
                        ],
                        [
                            'name'    => I18nValidator\PhoneNumber::class,
                            'options' => [
                                'allowed_types' => ['mobile'],
                                'country'       => $stringToUpperFilter->filter($siteCountryCode),
                                'messages'      => [
                                    I18nValidator\PhoneNumber::INVALID     => _("This is not a valid mobile number"),
                                    I18nValidator\PhoneNumber::NO_MATCH    => _("This is not a valid mobile number"),
                                    I18nValidator\PhoneNumber::UNSUPPORTED => _("This is not a valid mobile number"),
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        );
    }
}
