<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\Form;

use FwsDoctrineAuth\Entity\AuthUserInterface;
use FwsDoctrineAuth\Entity\TwoFactorAuthMethod;
use FwsDoctrineAuth\Exception\DoctrineAuthException;
use FwsDoctrineAuth\Model\AuthContainerStorage;
use Laminas\Form\Element;
use Laminas\Form\Form;

use function _;
use function array_key_exists;
use function sprintf;

/**
 * SelectTwoFactorAuthMethodForm
 */
class SelectTwoFactorAuthMethodForm extends Form
{
    /** @var string[] */
    private array $allowedMethods = [];

    /**
     * @param array $config
     */
    public function __construct(
        protected AuthContainerStorage $authContainerStorage,
        protected array $config
    ) {
        parent::__construct('select-method');
        $this->setAttribute('method', 'post');
    }

    public function init(): void
    {
        $this->add([
            'name'    => 'method',
            'type'    => Element\Radio::class,
            'options' => [
                'label'            => _('Select authentication method'),
                'label_attributes' => ['class' => 'required'],
                'value_options'    => [],
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
                'value' => _('Select'),
                'label' => _('Select'),
            ],
        ]);
    }

    /**
     * @param array $allowedMethods
     * @throws DoctrineAuthException
     */
    public function setAllowedMethods(array $allowedMethods): SelectTwoFactorAuthMethodForm
    {
        $options = $this->getMethodOptions($allowedMethods);
        $this->get('method')->setValueOptions($options);
        return $this;
    }

    /**
     * Get 2FA methods array from user attempting to login
     *
     * @return string[]
     * @throws DoctrineAuthException
     */
    private function getMethodOptions(array $allowedMethods): array
    {
        $methodsArray = [];
        if (! $allowedMethods) {
            return $methodsArray;
        }

        $identity = $this->authContainerStorage->getIdentity();
        if (! $identity instanceof AuthUserInterface) {
            return $methodsArray;
        }

        $authMethods = $identity->getAuthMethods() ?? [];
        /** @var TwoFactorAuthMethod $authMethod */
        foreach ($authMethods as $authMethod) {
            $name = $authMethod->getMethod();
            if (! array_key_exists($name, $allowedMethods)) {
                throw new DoctrineAuthException(sprintf('Adaptor for 2FA method %s not registered in config', $name));
            }
            $methodsArray[$name] = $allowedMethods[$name]::getTitle();
        }

        return $methodsArray;
    }
}
