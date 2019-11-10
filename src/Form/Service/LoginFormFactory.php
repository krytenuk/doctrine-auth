<?php

namespace FwsDoctrineAuth\Form\Service;

use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;
use FwsDoctrineAuth\Form\LoginForm;
use Doctrine\ORM\EntityManager;

/**
 * Description of LoginFormFactory
 *
 * @author Garry Childs <info@freedomwebservices.net>
 */
class LoginFormFactory implements FactoryInterface
{

    public function __invoke(ContainerInterface $container, $requestedName, Array $options = null)
    {
        return new LoginForm($container->get(EntityManager::class), $container->get('config'));
    }

}
