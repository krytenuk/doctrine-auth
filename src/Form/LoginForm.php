<?php

namespace FwsDoctrineAuth\Form;

class LoginForm extends DefaultForm
{
    
    public function init()
    {
        parent::init();
        $this->get('submit')->setValue('Login');
    }

}
