<?php

namespace FwsDoctrineAuth\Form\Service;

use FwsDoctrineAuth\Exception\DoctrineAuthException;
use FwsDoctrineAuth\Form\ResetPasswordForm;
use Laminas\Form\FormInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

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
     * @param array|null $options
     * @return FormInterface
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): FormInterface
    {   
        return new ResetPasswordForm($container->get('config'));
    }

}
