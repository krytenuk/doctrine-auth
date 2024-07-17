<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\View\Helper;

use Laminas\View\Helper\AbstractHelper;

use function array_slice;
use function count;
use function end;
use function explode;
use function floor;
use function implode;
use function str_repeat;
use function strlen;
use function substr;

/**
 * ObfuscateEmail
 * Hide part of email address with *'s
 */
class ObfuscateEmail extends AbstractHelper
{
    /**
     * Obfuscate email address
     */
    public function __invoke(string $email): string
    {
        $pieces = explode("@", $email);
        $name   = implode('@', array_slice($pieces, 0, count($pieces) - 1));
        $len    = (int)floor(strlen($name) / 2);

        return substr($name, 0, $len) . str_repeat('*', $len) . "@" . end($pieces);
    }
}
