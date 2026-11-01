<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\Mapping;

use Attribute;

/**
 * Optional. The user cannot log in while this property holds $deletedValue.
 * The default treats any date as deleted, for a nullable "deleted at" column.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class IsDeletedProperty
{
    public const ANY_DATE = 'DateTimeInterface';

    public function __construct(public readonly mixed $deletedValue = self::ANY_DATE)
    {
    }
}
