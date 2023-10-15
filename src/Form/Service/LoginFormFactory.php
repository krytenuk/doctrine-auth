<?php

namespace FwsDoctrineAuth\Form\Service;

use FwsDoctrineAuth\Exception\DoctrineAuthException;
use FwsDoctrineAuth\Form\LoginForm;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Doctrine\ORM\EntityManager;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Description of LoginFormFactory
 *
 * @author Garry Childs <info@freedomwebservices.net>
 */
class LoginFormFactory implements FactoryInterface
{

    /**
     * @param ContainerInterface $container
     * @param $requestedName
     * @param array|null $options
     * @return LoginForm
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws DoctrineAuthException
     */
    public function __invoke(ContainerInterface $container, $requestedName, Array $options = null): LoginForm
    {
        return new LoginForm(
            $container->get(EntityManager::class),
            $container->get('config')
        );
    }

}
