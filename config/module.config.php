<?php

/**
 * To override settings here, ensure your module is defined after FwsDoctrineAuth module.
 */

namespace FwsDoctrineAuth;

use Zend\Mvc\MvcEvent;
use Zend\ServiceManager\Factory\InvokableFactory;
use FwsDoctrineAuth\Listener\AuthListener;
use FwsDoctrineAuth\Listener\NavigationListener;
use FwsDoctrineAuth\Model;
use FwsDoctrineAuth\Controller;
use Zend\Router\Http\Segment;
use Zend\Router\Http\Literal;
use FwsDoctrineAuth\Service\AuthContainerFactory;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Zend\Authentication\AuthenticationService;
use FwsDoctrineAuth\Form;
use Doctrine\ORM\EntityManager;

return [
    'controllers' => [
        'factories' => [
            Controller\IndexController::class => Controller\Service\IndexControllerFactory::class,
        ],
    ],
    'router' => [
        'routes' => [
            'doctrine-auth' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/auth',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action' => 'index',
                    ],
                ],
                'child_routes' => [
                    'default' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '[/:action]',
                            'constraints' => [
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ],
                            'defaults' => [
                                'controller' => Controller\IndexController::class,
                                'action' => 'index',
                            ],
                        ],
                        'may_terminate' => TRUE,
                    ],
                ],
                'may_terminate' => TRUE,
            ],
        ],
    ],
    'view_manager' => array(
        'template_path_stack' => array(
            'fws-doctrine-auth' => __DIR__ . '/../view'
        ),
        'display_exceptions' => true,
    ),
    'service_manager' => [
        'factories' => [
            AuthListener::class => InvokableFactory::class,
            NavigationListener::class => InvokableFactory::class,
            Model\Acl::class => Model\Service\AclFactory::class,
            'authContainer' => AuthContainerFactory::class,
            Model\InitModel::class => Model\Service\InitModelFactory::class,
            Model\LoginModel::class => Model\Service\LoginModelFactory::class,
            Model\RegisterModel::class => Model\Service\RegisterModelFactory::class,
        ],
        'aliases' => [
            'acl' => Model\Acl::class,
            AuthenticationService::class => 'doctrine.authenticationservice.orm_default',
        ],
    ],
    'doctrine' => [
        'driver' => [
            __NAMESPACE__ . '_driver' => [
                'class' => AnnotationDriver::class,
                'paths' => array(__DIR__ . '/../src/Entity')
            ],
            'orm_default' => [
                'drivers' => [
                    __NAMESPACE__ . '\Entity' => __NAMESPACE__ . '_driver'
                ],
            ],
        ],
        'authentication' => [
            'orm_default' => [
                'object_manager' => EntityManager::class,
                'credential_callable' => 'FwsDoctrineAuth\Model\HashPassword::verifyCredential'
            ],
        ],
    ],
    'event_manager' => [
        'lazy_listeners' => [
            [
                'listener' => AuthListener::class,
                'method' => 'checkUser',
                'event' => MvcEvent::EVENT_ROUTE,
                'priority' => -100,
            ],
            [
                'listener' => NavigationListener::class,
                'method' => 'addAcl',
                'event' => MvcEvent::EVENT_RENDER,
                'priority' => -100,
            ],
        ],
    ],
    'form_elements' => [
        'factories' => [
            Form\LoginForm::class => Form\Service\LoginFormFactory::class,
            Form\RegisterForm::class => Form\Service\RegisterFormFactory::class,
        ],
    ],
];
