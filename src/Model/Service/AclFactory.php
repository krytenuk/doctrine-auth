<?php

namespace FwsDoctrineAuth\Model\Service;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use FwsDoctrineAuth\Model\Acl;

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
     * @param type $requestedName
     * @param array $options
     * @return Acl
     */
    public function __invoke(ContainerInterface $container, $requestedName, Array $options = null)
    {
        return new Acl($container->get('config'));
    }

}
