<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\Mapping;

use Attribute;

/**
 * The property holding the password hash.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class CredentialProperty
{
}
