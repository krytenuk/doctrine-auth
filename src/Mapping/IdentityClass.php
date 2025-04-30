<?php

namespace FwsDoctrineAuth\Mapping;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class IdentityClass
{
    public function __construct(
        protected string $identityProperty,
        protected string $credentialProperty,
    )
    {
    }

    public function getIdentityProperty(): string
    {
        return $this->identityProperty;
    }

    public function getCredentialProperty(): string
    {
        return $this->credentialProperty;
    }

}