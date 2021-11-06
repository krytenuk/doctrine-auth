<?php

namespace FwsDoctrineAuth\Form;

/**
 * LoginForm
 *
 * @author Garry Childs <info@freedomwebservices.net>
 */
class LoginForm extends DefaultForm
{
    
    /**
     * Create form elements
     * @return void
     */
    public function init(): void
    {
        parent::init();
        $this->get('submit')->setValue('Login');
    }

}
