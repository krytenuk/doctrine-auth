<?php

namespace FwsDoctrineAuth\View\Helper;

use FwsDoctrineAuth\Entity\AuthUserInterface;

/**
 * Helper trait for auto-completion of code in modern IDEs.
 **
 * @method ObfuscateEmail obfuscateEmail(string $email)
 * @method ObfuscatePhoneNumber obfuscatePhoneNumber(string $phone)
 * @method RequiredFieldsCheck requiredFieldsCheck(AuthUserInterface|null $identity, string|null $adaptor = null)
 */
trait ViewHelperTrait
{}