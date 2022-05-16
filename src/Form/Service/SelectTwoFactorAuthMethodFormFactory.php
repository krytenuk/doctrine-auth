<?php

namespace FwsDoctrineAuth\Form\Service;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

/**
 * SelectTwoFactorAuthMethodFormFactory
 *
 * @author Garry Childs <info@freedomwebservices.net>
 */
class SelectTwoFactorAuthMethodFormFactory implements FactoryInterface
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
        if (!isset($config['doctrineAuth']['selectTwoFactorAuthMethodForm'])) {
            throw new DoctrineAuthException('"selectTwoFactorAuthMethodForm" not found in config');
        }
        if (!class_exists($config['doctrineAuth']['selectTwoFactorAuthMethodForm'])) {
            throw new DoctrineAuthException(sprintf('Select two factor authentication form "%s" not found', $config['doctrineAuth']['selectTwoFactorAuthMethodForm']));
        }
        return new $config['doctrineAuth']['selectTwoFactorAuthMethodForm']($container->get('authContainer'));
    }

}
