<?php

namespace FwsDoctrineAuth\Model\Service;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use FwsDoctrineAuth\Model\Select2faModel;
use Doctrine\ORM\EntityManager;
use Laminas\Authentication\AuthenticationService;
use FwsDoctrineAuth\Model\TwoFactorAuthModel;

/**
 * Select2faModelFactory
 *
 * @author Garry Childs <info@freedomwebservices.net>
 */
class Select2faModelFactory implements FactoryInterface
{

    /**
     * Create forgot password model class
     * @param ContainerInterface $container
     * @param type $requestedName
     * @param array $options
     * @return ForgotPasswordModel
     * @throws DoctrineAuthException
     */
    public function __invoke(ContainerInterface $container, $requestedName, Array $options = null)
    {        
        return new Select2faModel(
                $container->get(TwoFactorAuthModel::class),
                $container->get(EntityManager::class),
                $container->get(AuthenticationService::class),
                $container->get('authContainer'),
                $container->get('config')
        );
    }

}
