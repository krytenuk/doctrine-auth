<?php

namespace FwsDoctrineAuth\Form\Service;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use FwsDoctrineAuth\Form\SelectTwoFactorAuthMethodForm;
use Laminas\Session\Container;

/**
 * SelectTwoFactorAuthMethodFormFactory
 *
 * @author Garry Childs <info@freedomwebservices.net>
 */
class SelectTwoFactorAuthMethodFormFactory implements FactoryInterface
{

    /**
     * 
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array $options
     * @return RegisterForm
     */
    public function __invoke(ContainerInterface $container, $requestedName, Array $options = null)
    {   
        return new SelectTwoFactorAuthMethodForm($container->get('authContainer'));
    }

}