<?php

namespace FwsDoctrineAuth\Form\Service;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

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
        $config = $container->get('config');
        if (!isset($config['doctrineAuth']['newPasswordForm'])) {
            throw new DoctrineAuthException('"newPasswordForm" not found in config');
        }
        if (!class_exists($config['doctrineAuth']['newPasswordForm'])) {
            throw new DoctrineAuthException(sprintf('New password form "%s" not found', $config['doctrineAuth']['newPasswordForm']));
        }  
        return new $config['doctrineAuth']['newPasswordForm']($container->get('config'));
    }

}
