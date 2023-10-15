<?php

namespace FwsDoctrineAuth\Model\Service;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use FwsDoctrineAuth\Model\RegisterModel;
use Doctrine\ORM\EntityManager;
use FwsDoctrineAuth\Model\LoginModel;
use FwsDoctrineAuth\Exception\DoctrineAuthException;
use FwsDoctrineAuth\Model\Acl;
use Psr\Container\NotFoundExceptionInterface;

/**
 * RegisterModel
 *
 * @author Garry Childs <info@freedomwebservices.net>
 */
class RegisterModelFactory implements FactoryInterface
{

    /**
     * Create registration model class
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return RegisterModel
     * @throws DoctrineAuthException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): RegisterModel
    {
        $config = $container->get('config');
        $registrationForm = $config['doctrineAuth']['registrationForm'] ?? null;
        if (!$registrationForm) {
            throw new DoctrineAuthException('"registrationForm" not found in config');
        }
        $formElementManager = $container->get('FormElementManager');
        if (!$formElementManager->has($registrationForm) && class_exists($registrationForm)) {
            throw new DoctrineAuthException(sprintf('Registration form "%s" not found', $registrationForm));
        }
        return new RegisterModel(
                $formElementManager->get($registrationForm),
                $container->get(EntityManager::class),
                $container->get(Acl::class),
                $container->get(LoginModel::class),
                $config
        );
    }

}
