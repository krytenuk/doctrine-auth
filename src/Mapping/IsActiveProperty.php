<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\Mapping;

use Attribute;

/**
 * Optional. The user can only log in while this property holds $activeValue.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class IsActiveProperty
{
    public function __construct(public readonly mixed $activeValue = true)
    {
    }
}
