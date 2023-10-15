<?php

namespace FwsDoctrineAuth\Form\Service;

use FwsDoctrineAuth\Exception\DoctrineAuthException;
use FwsDoctrineAuth\Form\ForgottenPasswordForm;
use FwsDoctrineAuth\Form\RegisterForm;
use Laminas\Form\FormInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Doctrine\ORM\EntityManager;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Description of EmailFormFactory
 *
 * @author Garry Childs <info@freedomwebservices.net>
 */
class ForgottenPasswordFormFactory implements FactoryInterface
{

    /**
     *
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return RegisterForm
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws DoctrineAuthException
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): FormInterface
    {   
        return new ForgottenPasswordForm(
            $container->get(EntityManager::class),
            $container->get('config')
        );
    }

}
