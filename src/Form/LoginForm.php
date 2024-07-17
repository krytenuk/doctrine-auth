<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\Form;

/**
 * LoginForm
 */
class LoginForm extends DefaultForm
{
    /**
     * Create form elements
     */
    public function init(): void
    {
        parent::init();
        $this->get('submit')->setValue('Login');
    }
}
