<?php

namespace FwsDoctrineAuth\Form;

/**
 * LoginForm
 *
 * @author Garry Childs <info@freedomwebservices.net>
 */
class LoginForm extends DefaultForm
{
    
    public function init()
    {
        parent::init();
        $this->get('submit')->setValue('Login');
    }

}
