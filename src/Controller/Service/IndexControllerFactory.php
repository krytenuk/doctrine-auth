<?php

namespace FwsDoctrineAuth\Controller\Service;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use FwsDoctrineAuth\Controller\IndexController;
use FwsDoctrineAuth\Model;

/**
 * IndexControllerFactory
 *
 * @author Garry Childs (Freedom Web Services)
 */
class IndexControllerFactory implements FactoryInterface
{

    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array $options
     * @return IndexController
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        return new IndexController(
                $container->get(Model\LoginModel::class), 
                $container->get(Model\RegisterModel::class),
                $container->get(Model\ForgotPasswordModel::class),
                $container->get('MvcTranslator')
        );
    }

}
