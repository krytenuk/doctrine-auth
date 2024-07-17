<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\View\Helper;

use Laminas\View\Helper\AbstractHelper;

use function str_pad;
use function strlen;
use function substr;

use const STR_PAD_LEFT;

/**
 * ObfuscateEmail
 * Hide part of email address with *'s
 */
class ObfuscatePhoneNumber extends AbstractHelper
{
    /**
     * Obfuscate email address
     */
    public function __invoke(string $phone): string
    {
        return str_pad(substr($phone, -4), strlen($phone), '*', STR_PAD_LEFT);
    }
}
