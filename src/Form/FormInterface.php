<?php

namespace FwsDoctrineAuth\Form;

/**
 *
 * @author Garry Childs <info@freedomwebservices.net>
 */
interface FormInterface
{
    public function addElements();
    
    public function addInputFilterSpecification();
}
