<?php

namespace FwsDoctrineAuth\Form\Service;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\Laminas\Hydrator\DoctrineObject;

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
     * @param array $options
     * @return RegisterForm
     */
    public function __invoke(ContainerInterface $container, $requestedName, Array $options = null)
    {
        $config = $container->get('config');
        if (!isset($config['doctrineAuth']['registrationForm'])) {
            throw new DoctrineAuthException('"registrationForm" not found in config');
        }
        if (!class_exists($config['doctrineAuth']['registrationForm'])) {
            throw new DoctrineAuthException(sprintf('Registration form "%s" not found', $config['doctrineAuth']['registrationForm']));
        }
        
        $entityManager = $container->get(EntityManager::class);
        $form = new $config['doctrineAuth']['registrationForm'](
                $entityManager,
                $config);
        $form->setHydrator(new DoctrineObject($entityManager));
        
        return $form;
    }

}
