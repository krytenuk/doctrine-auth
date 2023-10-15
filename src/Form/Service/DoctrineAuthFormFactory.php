<?php

namespace FwsDoctrineAuth\Form\Service;

use FwsDoctrineAuth\Exception\DoctrineAuthException;
use Laminas\Form\FormElementManager;
use Laminas\Form\FormInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Return a Doctrine Auth form by the configuration key ($requestedName)
 */
class DoctrineAuthFormFactory implements FactoryInterface
{
    const REGISTRATION_FORM = 'registrationForm';
    const LOGIN_FORM = 'loginForm';
    const FORGOTTEN_PASSWORD_FORM = 'forgottenPasswordForm';
    const RESET_PASSWORD_FORM = 'resetPasswordForm';
    const SELECT_2FA_METHODS_FORM = 'selectTwoFactorAuthMethodForm';
    const TWO_FACTOR_AUTHENTICATION_CODE_FORM = 'twoFactorAuthenticationCodeForm';
    /**
     * @param ContainerInterface $container
     * @param $requestedName
     * @param array|null $options
     * @return FormInterface
     * @throws DoctrineAuthException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): FormInterface
    {
        /** @var FormElementManager $formElementManager */
        $formElementManager = $container->get('FormElementManager');

        $config = $container->get('config')['doctrineAuth'] ?? null;
        if (!$config) {
            throw new DoctrineAuthException('"doctrineAuth" key not found in config');
        }

        $form = $config[$requestedName] ?? null;
        if (!$form) {
            throw new DoctrineAuthException(sprintf('"%s" key not found in config', $requestedName));
        }
        if (!$formElementManager->has($form) && class_exists($form)) {
            throw new DoctrineAuthException(sprintf('Doctrine auth form "%s" not found', $form));
        }

        return $formElementManager->get($form);
    }
}