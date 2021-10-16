<?php

namespace FwsDoctrineAuth\Form;

/**
 * LoginForm
 *
 * @author Garry Childs <info@freedomwebservices.net>
 */
class LoginForm extends DefaultForm
{
    
    public function init(): void
    {
        parent::init();
        $this->get('submit')->setValue('Login');
    }

}
