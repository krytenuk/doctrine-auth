<?php

namespace FwsDoctrineAuth\Form\Service;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;
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
        $config = $container->get('config');
        if (!isset($config['doctrineAuth']['loginForm'])) {
            throw new DoctrineAuthException('"loginForm" not found in config');
        }
        if (!class_exists($config['doctrineAuth']['loginForm'])) {
            throw new DoctrineAuthException(sprintf('Login form "%s" not found', $config['doctrineAuth']['loginForm']));
        }
        return new $config['doctrineAuth']['loginForm']($container->get(EntityManager::class), $container->get('config'));
    }

}
