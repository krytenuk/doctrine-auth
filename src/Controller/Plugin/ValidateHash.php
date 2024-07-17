<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\Controller\Plugin;

use Laminas\Mvc\Controller\Plugin\AbstractPlugin;
use Laminas\Session\Validator\Csrf;

class ValidateHash extends AbstractPlugin
{
    public function __construct(protected Csrf $csrfValidator)
    {
    }

    public function __invoke(): ValidateHash
    {
        return $this;
    }

    /**
     * Check if specified hash is valid
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
     */
    public function getHash(): string
    {
        return $this->csrfValidator->getHash();
    }
}
