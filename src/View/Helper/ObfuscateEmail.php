<?php

namespace FwsDoctrineAuth\View\Helper;

use Laminas\View\Helper\AbstractHelper;

/**
 * ObfuscateEmail
 * Hide part of email address with *'s
 *
 * @author Garry Childs <info@freedomwebservices.net>
 */
class ObfuscateEmail extends AbstractHelper
{

    /**
     * Obfuscate email address
     * @param string $email
     * @return type
     */
    public function __invoke(string $email)
    {
        $pieces = explode("@", $email);
        $name = implode('@', array_slice($pieces, 0, count($pieces) - 1));
        $len = floor(strlen($name) / 2);

        return substr($name, 0, $len) . str_repeat('*', $len) . "@" . end($pieces);
    }

}
