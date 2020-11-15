<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

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
