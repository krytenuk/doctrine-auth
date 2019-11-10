<?php

namespace FwsDoctrineAuth\Form;

/**
 * RegisterForm
 *
 * @author Garry Childs <info@freedomwebservices.net>
 */
class RegisterForm extends DefaultForm
{
    
    public function init()
    {
        parent::init();
        $this->get('submit')->setValue('Register');
    }

}
