<?php

namespace FwsDoctrineAuth\Form\Service;

use FwsDoctrineAuth\Exception\DoctrineAuthException;
use FwsDoctrineAuth\Form\SelectTwoFactorAuthMethodForm;
use Laminas\Form\FormInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

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
     * @param array|null $options
     * @return FormInterface
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): FormInterface
    {
        return new SelectTwoFactorAuthMethodForm(
            $container->get('authContainerStorage'),
            $container->get('config')
        );
    }

}
