<?php

namespace FwsDoctrineAuth\Model\Service;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use FwsDoctrineAuth\Model\TwoFactorAuthModel;
use Laminas\View\Renderer\PhpRenderer;
use FwsDoctrineAuth\Exception\DoctrineAuthException;
use DateTimeImmutable;
use DateTimeZone;

/**
 * Description of TwoFactorAuthModelFactory
 *
 * @author Garry Childs <info@freedomwebservices.net>
 */
class TwoFactorAuthModelFactory implements FactoryInterface
{
    /**
     * Create login model class
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array $options
     * @return LoginModel
     */
    public function __invoke(ContainerInterface $container, $requestedName, Array $options = null)
    {
        $datetime = new DateTimeImmutable('now');
        $config = $container->get('config');

        if (array_key_exists('timezone', $config) === true) {
            $datetime->setTimezone(new DateTimeZone($config['timezone']));
        }

        if (isset($config['doctrineAuth']['twoFactorAuthCodeForm']) === false) {
            throw new DoctrineAuthException('"twoFactorAuthCodeForm" key not found in config');
        }
        if (class_exists($config['doctrineAuth']['twoFactorAuthCodeForm']) === false) {
            throw new DoctrineAuthException(sprintf('Login form "%s" not found', $config['doctrineAuth']['twoFactorAuthCodeForm']));
        }
        
        return new TwoFactorAuthModel(
                $container->get('FormElementManager')->get($config['doctrineAuth']['selectTwoFactorAuthMethodForm']),
                $container->get('FormElementManager')->get($config['doctrineAuth']['twoFactorAuthCodeForm']),
                $container->get('authContainer'),
                $datetime,
                $container->get(PhpRenderer::class),
                $config
        );
    }
}
