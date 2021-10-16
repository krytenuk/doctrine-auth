<?php

namespace FwsDoctrineAuth\Model\Service;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;
use FwsDoctrineAuth\Model\Acl;

/**
 * Description of AclFactory
 *
 * @author Garry Childs <info@freedomwebservices.net>
 */
class AclFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, Array $options = null)
    {
        return new Acl($container->get('config'));
    }
}
