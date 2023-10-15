<?php

namespace FwsDoctrineAuth\Form\Service;

use FwsDoctrineAuth\Exception\DoctrineAuthException;
use FwsDoctrineAuth\Form\RegisterForm;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\Laminas\Hydrator\DoctrineObject;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Description of LoginFormFactory
 *
 * @author Garry Childs <info@freedomwebservices.net>
 */
class RegisterFormFactory implements FactoryInterface
{

    /**
     *
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return RegisterForm
     * @throws DoctrineAuthException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): RegisterForm
    {
        return new RegisterForm(
            $container->get(EntityManager::class),
            $container->get('config')
        );
    }

}
