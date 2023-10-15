<?php

namespace FwsDoctrineAuth\Model\TwoFactorAuthentication\Service;

use Exception;
use FwsDoctrineAuth\Exception\DoctrineAuthException;
use FwsDoctrineAuth\Form\Service\DoctrineAuthFormFactory;
use FwsDoctrineAuth\Model\TwoFactorAuthentication\AdaptorPluginManager;
use FwsDoctrineAuth\Model\TwoFactorAuthentication\TwoFactorAuthenticationModel;
use Laminas\Authentication\AuthenticationService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Description of TwoFactorAuthModelFactory
 *
 * @author Garry Childs <info@freedomwebservices.net>
 */
class TwoFactorAuthenticationModelFactory implements FactoryInterface
{
    /**
     * Create login model class
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return TwoFactorAuthenticationModel
     * @throws DoctrineAuthException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): TwoFactorAuthenticationModel
    {
        return new TwoFactorAuthenticationModel(
            $container->get(AdaptorPluginManager::class),
            $container->get('FormElementManager')->get(DoctrineAuthFormFactory::SELECT_2FA_METHODS_FORM),
            $container->get('FormElementManager')->get(DoctrineAuthFormFactory::TWO_FACTOR_AUTHENTICATION_CODE_FORM),
            $container->get('authContainerStorage'),
            $container->get(AuthenticationService::class),
            $container->get('config')
        );
    }
}
