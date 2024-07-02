<?php

namespace FwsDoctrineAuth\Controller\Plugin;

use FwsDoctrineAuth\Model\AuthContainerStorage;
use Laminas\Mvc\Controller\Plugin\AbstractPlugin;
use Laminas\Validator\Csrf;

class ValidateHash extends AbstractPlugin
{
    public function __construct(protected Csrf $csrfValidator)
    {}


    public function __invoke(): ValidateHash
    {
        return $this;
    }

    /**
     * Check if specified hash is valid
     * @param string $hash
     * @return bool
     */
    public function isValid(string $hash): bool
    {
        if (!$hash) {
            return false;
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