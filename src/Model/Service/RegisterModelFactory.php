<?php

namespace FwsDoctrineAuth\Model\Service;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;
use FwsDoctrineAuth\Model\RegisterModel;
use Doctrine\ORM\EntityManager;
use FwsDoctrineAuth\Model\LoginModel;
use FwsDoctrineAuth\Exception\DoctrineAuthException;

/**
 * RegisterModel
 *
 * @author Garry Childs <info@freedomwebservices.net>
 */
class RegisterModelFactory implements FactoryInterface
{

    public function __invoke(ContainerInterface $container, $requestedName, Array $options = null)
    {
        $config = $container->get('config');
        if (!isset($config['doctrineAuth']['registrationForm'])) {
            throw new DoctrineAuthException('"registrationForm" not found in config');
        }
        if (!class_exists($config['doctrineAuth']['registrationForm'])) {
            throw new DoctrineAuthException(sprintf('Registration form "%s" not found', $config['doctrineAuth']['registrationForm']));
        }
        return new RegisterModel(
                $container->get('FormElementManager')->get($config['doctrineAuth']['registrationForm']),
                $container->get(EntityManager::class),
                $container->get('acl'),
                $container->get(LoginModel::class),
                $config);
    }

}
