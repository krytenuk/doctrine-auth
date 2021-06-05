<?php

namespace FwsDoctrineAuth\Model\Service;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;
use FwsDoctrineAuth\Model\ForgotPasswordModel;
use FwsDoctrineAuth\Exception\DoctrineAuthException;
use Doctrine\ORM\EntityManager;
use Laminas\View\Renderer\PhpRenderer;

/**
 * ForgotPasswordModelFactory
 *
 * @author Garry Childs <info@freedomwebservices.net>
 */
class ForgotPasswordModelFactory implements FactoryInterface
{

    public function __invoke(ContainerInterface $container, $requestedName, Array $options = null)
    {
        $config = $container->get('config');
        if (!isset($config['doctrineAuth']['emailResetLinkForm'])) {
            throw new DoctrineAuthException('"emailResetLinkForm" not found in config');
        }
        if (!class_exists($config['doctrineAuth']['emailResetLinkForm'])) {
            throw new DoctrineAuthException(sprintf('Email reset link form "%s" not found', $config['doctrineAuth']['emailResetLinkForm']));
        }
        if (!isset($config['doctrineAuth']['newPasswordForm'])) {
            throw new DoctrineAuthException('"newPasswordForm" not found in config');
        }
        if (!class_exists($config['doctrineAuth']['newPasswordForm'])) {
            throw new DoctrineAuthException(sprintf('New password form "%s" not found', $config['doctrineAuth']['newPasswordForm']));
        }
        
        return new ForgotPasswordModel(
                $container->get(EntityManager::class),
                $container->get('FormElementManager')->get($config['doctrineAuth']['newPasswordForm']),
                $container->get('FormElementManager')->get($config['doctrineAuth']['emailResetLinkForm']),
                $container->get(PhpRenderer::class),
                $container->get('config')
        );
    }

}
