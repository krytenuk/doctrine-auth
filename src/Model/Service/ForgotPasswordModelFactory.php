<?php

namespace FwsDoctrineAuth\Model\Service;

use FwsDoctrineAuth\Form\Service\DoctrineAuthFormFactory;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use FwsDoctrineAuth\Model\ForgotPasswordModel;
use FwsDoctrineAuth\Exception\DoctrineAuthException;
use Doctrine\ORM\EntityManager;
use Laminas\View\Renderer\PhpRenderer;
use Psr\Container\NotFoundExceptionInterface;

/**
 * ForgotPasswordModelFactory
 *
 * @author Garry Childs <info@freedomwebservices.net>
 */
class ForgotPasswordModelFactory implements FactoryInterface
{

    /**
     * Create forgot password model class
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return ForgotPasswordModel
     * @throws DoctrineAuthException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): ForgotPasswordModel
    {
        return new ForgotPasswordModel(
            $container->get(EntityManager::class),
            $container->get('FormElementManager')->get(DoctrineAuthFormFactory::RESET_PASSWORD_FORM),
            $container->get('FormElementManager')->get(DoctrineAuthFormFactory::FORGOTTEN_PASSWORD_FORM),
            $container->get(PhpRenderer::class),
            $container->get('config')
        );
    }

}
