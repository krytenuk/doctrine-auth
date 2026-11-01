<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\Mapping;

use Attribute;

/**
 * The property a user logs in with, such as an email address.
 *
 * If the property is encrypted with krytenuk/doctrine-crypt it cannot be searched directly. Set $blindIndex to
 * the name of its blind index and users are looked up through that instead.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class IdentityProperty
{
    public function __construct(public readonly ?string $blindIndex = null)
    {
    }
}
