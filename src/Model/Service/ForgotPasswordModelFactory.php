<?php

namespace FwsDoctrineAuth\Model\Service;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;
use FwsDoctrineAuth\Model\ForgotPasswordModel;
use Doctrine\ORM\EntityManager;
use FwsDoctrineAuth\Form\ResetPasswordForm;
use FwsDoctrineAuth\Form\EmailForm;
use Laminas\View\Renderer\PhpRenderer;
use Laminas\View\Model\ViewModel;

/**
 * ForgotPasswordModelFactory
 *
 * @author Garry Childs <info@freedomwebservices.net>
 */
class ForgotPasswordModelFactory implements FactoryInterface
{

    public function __invoke(ContainerInterface $container, $requestedName, Array $options = null)
    {
        return new ForgotPasswordModel(
                $container->get(EntityManager::class),
                $container->get('FormElementManager')->get(ResetPasswordForm::class),
                $container->get('FormElementManager')->get(EmailForm::class),
                $container->get(PhpRenderer::class),
                $container->get('config')
        );
    }

}
