<?php

namespace FwsDoctrineAuth\View\Helper\Service;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;
use FwsDoctrineAuth\View\Helper\Decrypt;

/**
 * DecryptFactory
 *
 * @author Garry Childs (info@freedomwebservices.net)
 */
class DecryptFactory implements FactoryInterface
{

    /**
     * Create service
     *
     * @param ContainerInterface $container
     * @param string $name
     * @param null|array $options
     * @return Decrypt
     */
    public function __invoke(ContainerInterface $container, $name, array $options = null)
    {
        return new Decrypt($container->get('config'));
    }


}