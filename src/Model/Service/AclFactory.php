<?php

namespace FwsDoctrineAuth\Model\Service;

use FwsDoctrineAuth\Exception\DoctrineAuthException;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use FwsDoctrineAuth\Model\Acl;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Description of AclFactory
 *
 * @author Garry Childs <info@freedomwebservices.net>
 */
class AclFactory implements FactoryInterface
{

    /**
     * Create access control list class
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return Acl
     * @throws DoctrineAuthException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): Acl
    {
        return new Acl($container->get('config'));
    }

}
