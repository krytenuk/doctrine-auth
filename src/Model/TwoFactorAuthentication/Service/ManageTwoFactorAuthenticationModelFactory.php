<?php

namespace FwsDoctrineAuth\Model\TwoFactorAuthentication\Service;

use Doctrine\ORM\EntityManager;
use FwsDoctrineAuth\Exception\DoctrineAuthException;
use FwsDoctrineAuth\Model\TwoFactorAuthentication\ManageTwoFactorAuthenticationModel;
use Laminas\Authentication\AuthenticationService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Manage two factor authentication model
 *
 * @author Garry Childs <info@freedomwebservices.net>
 */
class ManageTwoFactorAuthenticationModelFactory implements FactoryInterface
{

    /**
     * Create forgot password model class
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return ManageTwoFactorAuthenticationModel
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws DoctrineAuthException
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): ManageTwoFactorAuthenticationModel
    {        
        return new ManageTwoFactorAuthenticationModel(
                $container->get(EntityManager::class),
                $container->get(AuthenticationService::class),
                $container->get('authContainerStorage'),
                $container->get('config')
        );
    }

}
