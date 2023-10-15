<?php

/**
 * To override settings here, ensure your module is defined after FwsDoctrineAuth module.
 */

namespace FwsDoctrineAuth;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use FwsDoctrineAuth\Controller;
use FwsDoctrineAuth\Controller\Plugin as ControllerPlugin;
use FwsDoctrineAuth\Form;
use FwsDoctrineAuth\Listener\AuthListener;
use FwsDoctrineAuth\Listener\NavigationListener;
use FwsDoctrineAuth\Model;
use FwsDoctrineAuth\Model\Service\AuthContainerStorageFactory;
use FwsDoctrineAuth\View\Helper as ViewHelper;
use Laminas\Authentication\AuthenticationService;
use Laminas\Mvc\MvcEvent;
use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;
use Laminas\ServiceManager\Factory\InvokableFactory;

return [
    'controllers' => [
        'factories' => [
            Controller\LoginController::class => Controller\Service\LoginControllerFactory::class,
            Controller\TwoFactorAuthenticationController::class => Controller\Service\TwoFactorAuthenticationControllerFactory::class,
            Controller\ManageTwoFactorAuthenticationController::class => Controller\Service\ManageTwoFactorAuthenticationControllerFactory::class,
            Controller\AppAuthenticationController::class => Controller\Service\AppAuthenticationControllerFactory::class,
        ],
    ],
    'router' => [
        'routes' => [
            'doctrine-auth' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/auth',
                    'defaults' => [
                        'controller' => Controller\LoginController::class,
                        'action' => 'index',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'default' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '[/:action]',
                            'constraints' => [
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ],
                            'defaults' => [
                                'controller' => Controller\LoginController::class,
                                'action' => 'index',
                            ],
                        ],
                        'may_terminate' => true,
                    ],
                    'login' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/login',
                            'defaults' => [
                                'controller' => Controller\LoginController::class,
                                'action' => 'login',
                            ],
                        ],
                        'may_terminate' => true,
                    ],
                    'register' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/register',
                            'constraints' => [
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ],
                            'defaults' => [
                                'controller' => Controller\LoginController::class,
                                'action' => 'register',
                            ],
                        ],
                        'may_terminate' => true,
                    ],
                    'logout' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/login',
                            'constraints' => [
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ],
                            'defaults' => [
                                'controller' => Controller\LoginController::class,
                                'action' => 'login',
                            ],
                        ],
                        'may_terminate' => true,
                    ],
                    'password-reset' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/password-reset[/:code]',
                            'constraints' => [
                                'code' => '[a-zA-Z0-9]*',
                            ],
                            'defaults' => [
                                'controller' => Controller\LoginController::class,
                                'action' => 'password-reset',
                            ],
                        ],
                        'may_terminate' => true,
                    ],
                    '2fa' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/2fa',
                            'defaults' => [
                                'controller' => Controller\TwoFactorAuthenticationController::class,
                                'action' => 'index',
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes' => [
                            'select-auth-method' => [
                                'type' => Literal::class,
                                'options' => [
                                    'route' => '/select-auth-method',
                                    'defaults' => [
                                        'controller' => Controller\TwoFactorAuthenticationController::class,
                                        'action' => 'select-auth-method',
                                    ],
                                ],
                                'may_terminate' => true,
                            ],
                            'authenticate' => [
                                'type' => Literal::class,
                                'options' => [
                                    'route' => '/authenticate',
                                    'defaults' => [
                                        'controller' => Controller\TwoFactorAuthenticationController::class,
                                        'action' => 'authenticate',
                                    ],
                                ],
                                'may_terminate' => true,
                            ],
                            'resend-code' => [
                                'type' => Literal::class,
                                'options' => [
                                    'route' => '/resend-code',
                                    'defaults' => [
                                        'controller' => Controller\TwoFactorAuthenticationController::class,
                                        'action' => 'resend-code',
                                    ],
                                ],
                                'may_terminate' => true,
                            ],
                            'list-methods' => [
                                'type' => Literal::class,
                                'options' => [
                                    'route' => '/list-methods',
                                    'defaults' => [
                                        'controller' => Controller\ManageTwoFactorAuthenticationController::class,
                                        'action' => 'list-methods',
                                    ],
                                ],
                                'may_terminate' => true,
                            ],
                            'add-method' => [
                                'type' => Segment::class,
                                'options' => [
                                    'route' => '/add-method/:method/:hash',
                                    'constraints' => [
                                        'method' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                        'hash' => '[a-zA-Z0-9-]*',
                                    ],
                                    'defaults' => [
                                        'controller' => Controller\ManageTwoFactorAuthenticationController::class,
                                        'action' => 'add-method',
                                    ],
                                ],
                                'may_terminate' => false,
                            ],
                            'remove-method' => [
                                'type' => Segment::class,
                                'options' => [
                                    'route' => '/remove-method/:method/:hash',
                                    'constraints' => [
                                        'method' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                        'hash' => '[a-zA-Z0-9-]*',
                                    ],
                                    'defaults' => [
                                        'controller' => Controller\ManageTwoFactorAuthenticationController::class,
                                        'action' => 'remove-method',
                                    ],
                                ],
                                'may_terminate' => true,
                            ],
                            'regenerate-app-secret' => [
                                'type' => Literal::class,
                                'options' => [
                                    'route' => '/regenerate-google-secret',
                                    'constraints' => [
                                        'method' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                        'hash' => '[a-zA-Z0-9-]*',
                                    ],
                                    'defaults' => [
                                        'controller' => Controller\AppAuthenticationController::class,
                                        'action' => 'regenerate-app-secret',
                                    ],
                                ],
                                'may_terminate' => true,
                            ],
                            'add-app-method' => [
                                'type' => Segment::class,
                                'options' => [
                                    'route' => '/add-method/app[/:hash]',
                                    'constraints' => [
                                        'code' => '[a-zA-Z0-9]*',
                                        'hash' => '[a-zA-Z0-9-]*',
                                    ],
                                    'defaults' => [
                                        'controller' => Controller\AppAuthenticationController::class,
                                        'action' => 'add-app-authentication-method',
                                        'hash' => null,
                                    ],
                                ],
                                'may_terminate' => true,
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            'fws-doctrine-auth' => __DIR__ . '/../view'
        ],
        'display_exceptions' => false,
    ],
    'service_manager' => [
        'factories' => [
            AuthListener::class => InvokableFactory::class,
            NavigationListener::class => InvokableFactory::class,
            Model\Acl::class => Model\Service\AclFactory::class,
            Model\AuthContainerStorage::class => AuthContainerStorageFactory::class,
            Model\LoginModel::class => Model\Service\LoginModelFactory::class,
            Model\TwoFactorAuthentication\TwoFactorAuthenticationModel::class => Model\TwoFactorAuthentication\Service\TwoFactorAuthenticationModelFactory::class,
            Model\TwoFactorAuthentication\ManageTwoFactorAuthenticationModel::class => Model\TwoFactorAuthentication\Service\ManageTwoFactorAuthenticationModelFactory::class,
            Model\TwoFactorAuthentication\AppAuthenticationMethodModel::class => Model\TwoFactorAuthentication\Service\AppAuthenticationMethodModelFactory::class,
            Model\RegisterModel::class => Model\Service\RegisterModelFactory::class,
            Model\ForgotPasswordModel::class => Model\Service\ForgotPasswordModelFactory::class,
//            Model\LoggingModel::class => Model\Service\LoggingModelFactory::class,
            Model\TwoFactorAuthentication\AdaptorPluginManager::class => Model\TwoFactorAuthentication\Service\AdaptorPluginManagerFactory::class,
            Model\TwoFactorAuthentication\Adapter\EmailAdapter::class => Model\TwoFactorAuthentication\Adapter\Service\EmailAdapterFactory::class,
            Model\TwoFactorAuthentication\Adapter\BulkSmsAdapter::class => Model\TwoFactorAuthentication\Adapter\Service\BulkSmsAdapterFactory::class,
            Model\TwoFactorAuthentication\Adapter\AuthenticationAppAdapter::class => Model\TwoFactorAuthentication\Adapter\Service\AuthenticationAppAdapterFactory::class
        ],
        'aliases' => [
            'acl' => Model\Acl::class,
            'authContainerStorage' => Model\AuthContainerStorage::class,
            AuthenticationService::class => 'doctrine.authenticationservice.orm_default',
        ],
    ],
    'doctrine' => [
        'driver' => [
            __NAMESPACE__ . '_driver' => [
                'class' => AnnotationDriver::class,
                'paths' => [__DIR__ . '/../src/Entity']
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
                'event' => MvcEvent::EVENT_DISPATCH,
                'priority' => 1000,
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
            // Default Doctrine Auth Forms
            Form\RegisterForm::class => Form\Service\RegisterFormFactory::class,
            Form\ResetPasswordForm::class => Form\Service\ResetPasswordFormFactory::class,
            Form\ForgottenPasswordForm::class => Form\Service\ForgottenPasswordFormFactory::class,
            Form\SelectTwoFactorAuthMethodForm::class => Form\Service\SelectTwoFactorAuthMethodFormFactory::class,
            Form\TwoFactorAuthenticationCodeForm::class => InvokableFactory::class,
            // Load forms using configuration values
            Form\Service\DoctrineAuthFormFactory::REGISTRATION_FORM => Form\Service\DoctrineAuthFormFactory::class,
            Form\Service\DoctrineAuthFormFactory::LOGIN_FORM => Form\Service\DoctrineAuthFormFactory::class,
            Form\Service\DoctrineAuthFormFactory::FORGOTTEN_PASSWORD_FORM => Form\Service\DoctrineAuthFormFactory::class,
            Form\Service\DoctrineAuthFormFactory::RESET_PASSWORD_FORM => Form\Service\DoctrineAuthFormFactory::class,
            Form\Service\DoctrineAuthFormFactory::TWO_FACTOR_AUTHENTICATION_CODE_FORM => Form\Service\DoctrineAuthFormFactory::class,
            Form\Service\DoctrineAuthFormFactory::SELECT_2FA_METHODS_FORM => Form\Service\DoctrineAuthFormFactory::class,
        ],
    ],
    'view_helpers' => [
        'factories' => [
            ViewHelper\ObfuscateEmail::class => InvokableFactory::class,
            ViewHelper\ObfuscatePhoneNumber::class => InvokableFactory::class,
            ViewHelper\RequiredFieldsCheck::class => InvokableFactory::class
        ],
        'aliases' => [
            'obfuscateEmail' => ViewHelper\ObfuscateEmail::class,
            'obfuscatePhoneNumber' => ViewHelper\ObfuscatePhoneNumber::class,
            'requiredFieldsCheck' => ViewHelper\RequiredFieldsCheck::class,
        ],
    ],
    'controller_plugins' => [
        'factories' => [
            ControllerPlugin\GetRedirect::class => ControllerPlugin\Service\GetRedirectFactory::class,
            ControllerPlugin\IsIpBlocked::class => ControllerPlugin\Service\IsIpBlockedFactory::class,
            ControllerPlugin\BlockIP::class => ControllerPlugin\Service\BlockIpFactory::class,
            ControllerPlugin\LogFailedAttempt::class => ControllerPlugin\Service\LogFailedAttemptFactory::class,
            ControllerPlugin\LogSuccessfulLogin::class => ControllerPlugin\Service\LogSuccessfulLoginFactory::class,
            ControllerPlugin\ValidateHash::class => ControllerPlugin\Service\ValidateHashFactory::class,
        ],
        'aliases' => [
            'getAuthRedirect' => ControllerPlugin\GetRedirect::class,
            'isIpBlocked' => ControllerPlugin\IsIpBlocked::class,
            'blockIpAddress' => ControllerPlugin\BlockIP::class,
            'logFailedLoginAttempt' => ControllerPlugin\LogFailedAttempt::class,
            'logSuccessfulLogin' => ControllerPlugin\LogSuccessfulLogin::class,
            'validateHash' => ControllerPlugin\ValidateHash::class,
        ],
    ],
];
