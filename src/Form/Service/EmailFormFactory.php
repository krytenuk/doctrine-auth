<?php

namespace FwsDoctrineAuth\Form\Service;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;
use FwsDoctrineAuth\Form\EmailForm;
use Doctrine\ORM\EntityManager;

/**
 * Description of EmailFormFactory
 *
 * @author Garry Childs <info@freedomwebservices.net>
 */
class EmailFormFactory implements FactoryInterface
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
        return new EmailForm($container->get(EntityManager::class), $container->get('config'));
    }

}
