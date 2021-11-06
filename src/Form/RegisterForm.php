<?php

namespace FwsDoctrineAuth\Form;

/**
 * RegisterForm
 *
 * @author Garry Childs <info@freedomwebservices.net>
 */
class RegisterForm extends DefaultForm
{
    
    /**
     * Create form elemets
     * @return void
     */
    public function init(): void
    {
        parent::init();
        $this->get('submit')->setValue('Register');
    }

}
