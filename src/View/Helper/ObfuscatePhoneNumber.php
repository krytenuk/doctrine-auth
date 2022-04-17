<?php

namespace FwsDoctrineAuth\View\Helper;

use Laminas\View\Helper\AbstractHelper;

/**
 * ObfuscateEmail
 * Hide part of email address with *'s
 *
 * @author Garry Childs <info@freedomwebservices.net>
 */
class ObfuscatePhoneNumber extends AbstractHelper
{

    /**
     * Obfuscate email address
     * @param string $email
     * @return type
     */
    public function __invoke(string $phone)
    {
        return str_pad(substr($phone, -4), strlen($phone), '*', STR_PAD_LEFT);
    }

}
