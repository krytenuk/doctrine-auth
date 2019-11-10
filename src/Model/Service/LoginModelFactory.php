<?php

namespace FwsDoctrineAuth\Model\Service;

use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;
use FwsDoctrineAuth\Model\LoginModel;
use FwsDoctrineAuth\Form\LoginForm;
use Zend\Authentication\AuthenticationService;
use Doctrine\ORM\EntityManager;
use Zend\Session\SessionManager;
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

        return new LoginModel(
                $container->get('FormElementManager')->get(LoginForm::class),
                $container->get(AuthenticationService::class),
                $container->get(EntityManager::class),
                $datetime,
                $container->get('authContainer'),
                $container->get(SessionManager::class),
                $container->get('acl'),
                $config);
    }

}
