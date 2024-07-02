<?php

namespace FwsDoctrineAuth\Controller;

use Laminas\Mvc\Plugin\FlashMessenger\FlashMessenger;

/**
 * @method FlashMessenger flashMessenger()
 */
trait CheckHashTrait
{
    private function checkHash(): bool
    {
        $hash = (string) $this->params()->fromRoute('hash', '');
        if (!$this->validateHash()->isValid($hash)) {
            $this->flashMessenger()->addErrorMessage(_('The request could not be validated, please try again'));
            return false;
        }

        return true;
    }
}