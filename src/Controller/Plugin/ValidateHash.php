<?php

namespace FwsDoctrineAuth\Controller\Plugin;

use FwsDoctrineAuth\Model\AuthContainerStorage;
use Laminas\Mvc\Controller\Plugin\AbstractPlugin;
use Laminas\Validator\Csrf;

class ValidateHash extends AbstractPlugin
{
    private Csrf $csrfValidator;

    public function __construct(protected AuthContainerStorage $authContainerStorage)
    {
        $this->csrfValidator = new Csrf(['session' => $this->authContainerStorage]);
    }


    public function __invoke(?string $hash = null): ValidateHash|bool
    {
        if ($hash === null) {
            return $this;
        }
        return $this->csrfValidator->isValid($hash);
    }

    /**
     * Return the generated hash value
     * @return string
     */
    public function getHash(): string
    {
        return $this->csrfValidator->getHash();
    }

}