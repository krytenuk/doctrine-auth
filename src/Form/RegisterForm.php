<?php

namespace FwsDoctrineAuth\Form;

/**
 * RegisterForm
 *
 * @author Garry Childs <info@freedomwebservices.net>
 */
class RegisterForm extends DefaultForm
{
    
    public function init(): void
    {
        parent::init();
        $this->get('submit')->setValue('Register');
    }

}
