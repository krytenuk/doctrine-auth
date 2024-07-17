<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\Controller;

use Laminas\Mvc\Plugin\FlashMessenger\FlashMessenger;

use function _;

/**
 * @method FlashMessenger flashMessenger()
 */
trait CheckHashTrait
{
    private function checkHash(): bool
    {
        $hash = (string) $this->params()->fromRoute('hash', '');
        if (! $this->validateHash()->isValid($hash)) {
            $this->flashMessenger()->addErrorMessage(_('The request could not be validated, please try again'));
            return false;
        }

        return true;
    }
}
