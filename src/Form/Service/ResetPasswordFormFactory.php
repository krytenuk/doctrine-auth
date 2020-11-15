<?php

namespace FwsDoctrineAuth\Form\Service;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;
use FwsDoctrineAuth\Form\ResetPasswordForm;

/**
 * ForgotPasswordFormFactory
 *
 * @author Garry Childs <info@freedomwebservices.net>
 */
class ResetPasswordFormFactory implements FactoryInterface
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
        return new ResetPasswordForm($container->get('config'));
    }

}
