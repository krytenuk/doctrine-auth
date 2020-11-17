<?php

namespace FwsDoctrineAuth\Model\Service;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;
use FwsDoctrineAuth\Model\LoginModel;
use FwsDoctrineAuth\Form\LoginForm;
use Laminas\Authentication\AuthenticationService;
use Doctrine\ORM\EntityManager;
use Laminas\Session\SessionManager;
use DateTime;
use DateTimeZone;

/**
 * Description of LoginModelFactory
 *
 * @author Garry Childs (Freedom Web Services)
 */
class LoginModelFactory implements FactoryInterface
{

    /**
     * 
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array $options
     * @return LoginModel
     */
    public function __invoke(ContainerInterface $container, $requestedName, Array $options = null)
    {
        $datetime = new DateTime();
        $config = $container->get('config');

        if (array_key_exists('timezone', $config)) {
            $datetime->setTimezone(new DateTimeZone($config['timezone']));
        }
        
        if (!isset($config['doctrineAuth']['loginForm'])) {
            throw new DoctrineAuthException('"loginForm" not found in config');
        }
        if (!class_exists($config['doctrineAuth']['loginForm'])) {
            throw new DoctrineAuthException(sprintf('Login form "%s" not found', $config['doctrineAuth']['loginForm']));
        }

        return new LoginModel(
                $container->get('FormElementManager')->get($config['doctrineAuth']['loginForm']),
                $container->get(AuthenticationService::class),
                $container->get(EntityManager::class),
                $datetime,
                $container->get('authContainer'),
                $container->get(SessionManager::class),
                $container->get('acl'),
                $config);
    }

}
