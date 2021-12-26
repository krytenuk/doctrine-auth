<?php

namespace FwsDoctrineAuth\Form\Service;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
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
        $config = $container->get('config');
        if (!isset($config['doctrineAuth']['emailResetLinkForm'])) {
            throw new DoctrineAuthException('"emailResetLinkForm" not found in config');
        }
        if (!class_exists($config['doctrineAuth']['emailResetLinkForm'])) {
            throw new DoctrineAuthException(sprintf('Email reset link form "%s" not found', $config['doctrineAuth']['emailResetLinkForm']));
        }
        return new $config['doctrineAuth']['emailResetLinkForm']($container->get(EntityManager::class), $container->get('config'));
    }

}
