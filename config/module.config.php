<?php

declare(strict_types=1);

/**
 * To override settings here, ensure your module is defined after FwsDoctrineAuth module.
 */

namespace FwsDoctrineAuth;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use FwsDoctrineAuth\Command;
use FwsDoctrineAuth\Controller;
use FwsDoctrineAuth\Controller\Plugin as ControllerPlugin;
use FwsDoctrineAuth\Form;
use FwsDoctrineAuth\Listener;
use FwsDoctrineAuth\Model;
use FwsDoctrineAuth\Model\TwoFactorAuthentication\AdaptorPluginManager;
use FwsDoctrineAuth\Model\TwoFactorAuthentication\ManageTwoFactorAuthenticationModel;
use FwsDoctrineAuth\Model\TwoFactorAuthentication\TwoFactorAuthenticationModel;
use FwsDoctrineAuth\View\Helper as ViewHelper;
use Laminas\Authentication\AuthenticationService;
use Laminas\Mvc\MvcEvent;
use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;
use Laminas\ServiceManager\AbstractFactory\ConfigAbstractFactory;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Laminas\Session\SessionManager;
use Laminas\Session\Validator\Csrf;
use Laminas\View\Renderer\PhpRenderer;
use Psr\Container\ContainerInterface;

return [
    'controllers'     => [
        'factories' => [
            Controller\LoginController::class                         => ConfigAbstractFactory::class,
            Controller\TwoFactorAuthenticationController::class       => ConfigAbstractFactory::class,
            Controller\ManageTwoFactorAuthenticationController::class => ConfigAbstractFactory::class,
            Controller\AppAuthenticationController::class             => ConfigAbstractFactory::class,
        ],
    ],
    'router'          => [
        'routes' => [
            'doctrine-auth' => [
                'type'          => Literal::class,
                'options'       => [
                    'route'    => '/auth',
                    'defaults' => [
                        'controller' => Controller\LoginController::class,
                        'action'     => 'index',
                    ],
                ],
                'may_terminate' => true,
                'child_routes'  => [
                    'default'        => [
                        'type'          => Segment::class,
                        'options'       => [
                            'route'       => '[/:action]',
                            'constraints' => [
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ],
                            'defaults'    => [
                                'controller' => Controller\LoginController::class,
                                'action'     => 'index',
                            ],
                        ],
                        'may_terminate' => true,
                    ],
                    'login'          => [
                        'type'          => Segment::class,
                        'options'       => [
                            'route'    => '/login',
                            'defaults' => [
                                'controller' => Controller\LoginController::class,
                                'action'     => 'login',
                            ],
                        ],
                        'may_terminate' => true,
                    ],
                    'register'       => [
                        'type'          => Segment::class,
                        'options'       => [
                            'route'       => '/register',
                            'constraints' => [
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ],
                            'defaults'    => [
                                'controller' => Controller\LoginController::class,
                                'action'     => 'register',
                            ],
                        ],
                        'may_terminate' => true,
                    ],
                    'logout'         => [
                        'type'          => Segment::class,
                        'options'       => [
                            'route'       => '/login',
                            'constraints' => [
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ],
                            'defaults'    => [
                                'controller' => Controller\LoginController::class,
                                'action'     => 'login',
                            ],
                        ],
                        'may_terminate' => true,
                    ],
                    'password-reset' => [
                        'type'          => Segment::class,
                        'options'       => [
                            'route'       => '/password-reset[/:code]',
                            'constraints' => [
                                'code' => '[a-zA-Z0-9]*',
                            ],
                            'defaults'    => [
                                'controller' => Controller\LoginController::class,
                                'action'     => 'password-reset',
                            ],
                        ],
                        'may_terminate' => true,
                    ],
                    '2fa'            => [
                        'type'          => Literal::class,
                        'options'       => [
                            'route'    => '/2fa',
                            'defaults' => [
                                'controller' => Controller\TwoFactorAuthenticationController::class,
                                'action'     => 'index',
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes'  => [
                            'select-auth-method'    => [
                                'type'          => Literal::class,
                                'options'       => [
                                    'route'    => '/select-auth-method',
                                    'defaults' => [
                                        'controller' => Controller\TwoFactorAuthenticationController::class,
                                        'action'     => 'select-auth-method',
                                    ],
                                ],
                                'may_terminate' => true,
                            ],
                            'authenticate'          => [
                                'type'          => Literal::class,
                                'options'       => [
                                    'route'    => '/authenticate',
                                    'defaults' => [
                                        'controller' => Controller\TwoFactorAuthenticationController::class,
                                        'action'     => 'authenticate',
                                    ],
                                ],
                                'may_terminate' => true,
                            ],
                            'resend-code'           => [
                                'type'          => Literal::class,
                                'options'       => [
                                    'route'    => '/resend-code',
                                    'defaults' => [
                                        'controller' => Controller\TwoFactorAuthenticationController::class,
                                        'action'     => 'resend-code',
                                    ],
                                ],
                                'may_terminate' => true,
                            ],
                            'list-methods'          => [
                                'type'          => Literal::class,
                                'options'       => [
                                    'route'    => '/list-methods',
                                    'defaults' => [
                                        'controller' => Controller\ManageTwoFactorAuthenticationController::class,
                                        'action'     => 'list-methods',
                                    ],
                                ],
                                'may_terminate' => true,
                            ],
                            'add-method'            => [
                                'type'          => Segment::class,
                                'options'       => [
                                    'route'       => '/add-method/:method/:hash',
                                    'constraints' => [
                                        'method' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                        'hash'   => '[a-zA-Z0-9-]*',
                                    ],
                                    'defaults'    => [
                                        'controller' => Controller\ManageTwoFactorAuthenticationController::class,
                                        'action'     => 'add-method',
                                    ],
                                ],
                                'may_terminate' => false,
                            ],
                            'remove-method'         => [
                                'type'          => Segment::class,
                                'options'       => [
                                    'route'       => '/remove-method/:method/:hash',
                                    'constraints' => [
                                        'method' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                        'hash'   => '[a-zA-Z0-9-]*',
                                    ],
                                    'defaults'    => [
                                        'controller' => Controller\ManageTwoFactorAuthenticationController::class,
                                        'action'     => 'remove-method',
                                    ],
                                ],
                                'may_terminate' => true,
                            ],
                            'regenerate-app-secret' => [
                                'type'          => Literal::class,
                                'options'       => [
                                    'route'       => '/regenerate-google-secret',
                                    'constraints' => [
                                        'method' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                        'hash'   => '[a-zA-Z0-9-]*',
                                    ],
                                    'defaults'    => [
                                        'controller' => Controller\AppAuthenticationController::class,
                                        'action'     => 'regenerate-app-secret',
                                    ],
                                ],
                                'may_terminate' => true,
                            ],
                            'add-app-method'        => [
                                'type'          => Segment::class,
                                'options'       => [
                                    'route'       => '/add-method/app[/:hash]',
                                    'constraints' => [
                                        'code' => '[a-zA-Z0-9]*',
                                        'hash' => '[a-zA-Z0-9-]*',
                                    ],
                                    'defaults'    => [
                                        'controller' => Controller\AppAuthenticationController::class,
                                        'action'     => 'add-app-authentication-method',
                                        'hash'       => null,
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
    'view_manager'    => [
        'template_path_stack' => [
            'fws-doctrine-auth' => __DIR__ . '/../view',
        ],
        'display_exceptions'  => false,
    ],
    'service_manager' => [
        'abstract_factories' => [
            ConfigAbstractFactory::class,
        ],
        'factories'          => [
            Listener\AuthListener::class                                      => InvokableFactory::class,
            Listener\NavigationListener::class                                => InvokableFactory::class,
            Listener\LayoutListener::class                                    => InvokableFactory::class,
            Model\Acl::class                                                  => ConfigAbstractFactory::class,
            Model\AuthContainerStorage::class                                 => function () {
                return new Model\AuthContainerStorage('auth');
            },
            Model\LoginModel::class                                           => ConfigAbstractFactory::class,
            TwoFactorAuthenticationModel::class                               => ConfigAbstractFactory::class,
            ManageTwoFactorAuthenticationModel::class                         => ConfigAbstractFactory::class,
            Model\TwoFactorAuthentication\AppAuthenticationMethodModel::class => ConfigAbstractFactory::class,
            Model\RegisterModel::class                                        => ConfigAbstractFactory::class,
            Model\ForgotPasswordModel::class                                  => ConfigAbstractFactory::class,
            Model\GetClientIpAddress::class                                   => ConfigAbstractFactory::class,
            AdaptorPluginManager::class                                       => Model\TwoFactorAuthentication\Service\AdaptorPluginManagerFactory::class,
            Model\TwoFactorAuthentication\Adapter\EmailAdapter::class         => ConfigAbstractFactory::class,
            Model\TwoFactorAuthentication\Adapter\BulkSmsAdapter::class       => ConfigAbstractFactory::class,
            Model\TwoFactorAuthentication\Adapter\AuthenticationAppAdapter::class => InvokableFactory::class,
            Command\InitCommand::class                                            => ConfigAbstractFactory::class,
            Command\CreateUserCommand::class                                      => ConfigAbstractFactory::class,
            AuthenticationService::class                                          => function ($serviceManager) {
                return $serviceManager->get('doctrine.authenticationservice.orm_default');
            },
        ],
        'aliases'            => [
            'acl'                        => Model\Acl::class,
            'authContainerStorage'       => Model\AuthContainerStorage::class,
            AuthenticationService::class => 'doctrine.authenticationservice.orm_default',
        ],
    ],
    'doctrine'        => [
        'driver'         => [
            __NAMESPACE__ . '_driver' => [
                'class' => AttributeDriver::class,
                'paths' => [__DIR__ . '/../src/Entity'],
            ],
            'orm_default'             => [
                'drivers' => [
                    __NAMESPACE__ . '\Entity' => __NAMESPACE__ . '_driver',
                ],
            ],
        ],
        'authentication' => [
            'orm_default' => [
                'object_manager'      => EntityManager::class,
                'credential_callable' => 'FwsDoctrineAuth\Model\HashPassword::verifyCredential',
            ],
        ],
    ],
    'event_manager'   => [
        'lazy_listeners' => [
            [
                'listener' => Listener\AuthListener::class,
                'method'   => 'checkUser',
                'event'    => MvcEvent::EVENT_DISPATCH,
                'priority' => 1000,
            ],
            [
                'listener' => Listener\NavigationListener::class,
                'method'   => 'addAcl',
                'event'    => MvcEvent::EVENT_RENDER,
                'priority' => -100,
            ],
            [
                'listener' => Listener\LayoutListener::class,
                'method'   => 'setLayout',
                'event'    => MvcEvent::EVENT_DISPATCH,
                'priority' => -100,
            ],
        ],
    ],
    // @todo Document the new ConfigAbstractFactory::class for auto-wiring forms and how to override forms using this. Existing documentation shows old factories method
    // @todo Document the fact form elements can have attributes set without overriding the form, e.g. echo $this->formPassword()->render($form->get($form->getCredentialProperty())->setAttribute('class', 'form-control'));
    'form_elements'              => [
        'factories' => [
            // Default Doctrine Auth Forms
            Form\LoginFormAbstract::class                       => ConfigAbstractFactory::class,
            Form\RegisterFormAbstract::class                    => ConfigAbstractFactory::class,
            Form\ResetPasswordForm::class               => ConfigAbstractFactory::class,
            Form\ForgottenPasswordForm::class           => ConfigAbstractFactory::class,
            Form\SelectTwoFactorAuthMethodForm::class   => ConfigAbstractFactory::class,
            Form\TwoFactorAuthenticationCodeForm::class => InvokableFactory::class,
            // Load forms using configuration values
            Form\Service\DoctrineAuthFormFactory::REGISTRATION_FORM                   => Form\Service\DoctrineAuthFormFactory::class,
            Form\Service\DoctrineAuthFormFactory::LOGIN_FORM                          => Form\Service\DoctrineAuthFormFactory::class,
            Form\Service\DoctrineAuthFormFactory::FORGOTTEN_PASSWORD_FORM             => Form\Service\DoctrineAuthFormFactory::class,
            Form\Service\DoctrineAuthFormFactory::RESET_PASSWORD_FORM                 => Form\Service\DoctrineAuthFormFactory::class,
            Form\Service\DoctrineAuthFormFactory::TWO_FACTOR_AUTHENTICATION_CODE_FORM => Form\Service\DoctrineAuthFormFactory::class,
            Form\Service\DoctrineAuthFormFactory::SELECT_2FA_METHODS_FORM             => Form\Service\DoctrineAuthFormFactory::class,
        ],
    ],
    'view_helpers'               => [
        'factories' => [
            ViewHelper\ObfuscateEmail::class       => InvokableFactory::class,
            ViewHelper\ObfuscatePhoneNumber::class => InvokableFactory::class,
            ViewHelper\RequiredFieldsCheck::class  => InvokableFactory::class,
        ],
        'aliases'   => [
            'obfuscateEmail'       => ViewHelper\ObfuscateEmail::class,
            'obfuscatePhoneNumber' => ViewHelper\ObfuscatePhoneNumber::class,
            'requiredFieldsCheck'  => ViewHelper\RequiredFieldsCheck::class,
        ],
    ],
    'controller_plugins'         => [
        'factories' => [
            ControllerPlugin\GetRedirect::class        => ConfigAbstractFactory::class,
            ControllerPlugin\IsIpBlocked::class        => ConfigAbstractFactory::class,
            ControllerPlugin\BlockIP::class            => ConfigAbstractFactory::class,
            ControllerPlugin\LogFailedAttempt::class   => ConfigAbstractFactory::class,
            ControllerPlugin\LogSuccessfulLogin::class => ConfigAbstractFactory::class,
            ControllerPlugin\ValidateHash::class       => function (ContainerInterface $container) {
                return new ControllerPlugin\ValidateHash(new Csrf(['session' => $container->get(Model\AuthContainerStorage::class)]));
            },
        ],
        'aliases'   => [
            'getAuthRedirect'       => ControllerPlugin\GetRedirect::class,
            'isIpBlocked'           => ControllerPlugin\IsIpBlocked::class,
            'blockIpAddress'        => ControllerPlugin\BlockIP::class,
            'logFailedLoginAttempt' => ControllerPlugin\LogFailedAttempt::class,
            'logSuccessfulLogin'    => ControllerPlugin\LogSuccessfulLogin::class,
            'validateHash'          => ControllerPlugin\ValidateHash::class,
        ],
    ],
    ConfigAbstractFactory::class => [
        /* Controllers */
        Controller\LoginController::class                         => [
            Model\LoginModel::class,
            Model\RegisterModel::class,
            Model\ForgotPasswordModel::class,
            ManageTwoFactorAuthenticationModel::class,
            TwoFactorAuthenticationModel::class,
        ],
        Controller\TwoFactorAuthenticationController::class       => [
            TwoFactorAuthenticationModel::class,
        ],
        Controller\ManageTwoFactorAuthenticationController::class => [
            ManageTwoFactorAuthenticationModel::class,
        ],
        Controller\AppAuthenticationController::class             => [
            Model\TwoFactorAuthentication\AppAuthenticationMethodModel::class,
        ],

        /* Controller Plugins */
        ControllerPlugin\GetRedirect::class        => [
            Model\Acl::class,
            Model\AuthContainerStorage::class,
        ],
        ControllerPlugin\IsIpBlocked::class        => [
            EntityManager::class,
            Model\GetClientIpAddress::class,
            'config',
        ],
        ControllerPlugin\BlockIP::class            => [
            EntityManager::class,
            Model\GetClientIpAddress::class,
            'config',
        ],
        ControllerPlugin\LogFailedAttempt::class   => [
            EntityManager::class,
            Model\GetClientIpAddress::class,
        ],
        ControllerPlugin\LogSuccessfulLogin::class => [
            EntityManager::class,
            AuthenticationService::class,
        ],

        /* Forms */
        /* Default Auth Forms */
        Form\LoginFormAbstract::class                     => [
            EntityManager::class,
            'config',
        ],
        Form\RegisterFormAbstract::class                  => [
            EntityManager::class,
            'config',
        ],
        Form\ResetPasswordForm::class             => [
            'config',
        ],
        Form\ForgottenPasswordForm::class         => [
            EntityManager::class,
            'config',
        ],
        Form\SelectTwoFactorAuthMethodForm::class => [
            'authContainerStorage',
            'config',
        ],

        /* Models */
        Model\Acl::class                                                  => [
            'config',
        ],
        Model\LoginModel::class                                           => [
            'FormElementManager',
            AuthenticationService::class,
            EntityManager::class,
            'authContainerStorage',
            SessionManager::class,
            Model\Acl::class,
            'config',
        ],
        TwoFactorAuthenticationModel::class                               => [
            AdaptorPluginManager::class,
            'FormElementManager',
            'authContainerStorage',
            AuthenticationService::class,
            'config',
        ],
        ManageTwoFactorAuthenticationModel::class                         => [
            EntityManager::class,
            AuthenticationService::class,
            'authContainerStorage',
            'config',
        ],
        Model\TwoFactorAuthentication\AppAuthenticationMethodModel::class => [
            ManageTwoFactorAuthenticationModel::class,
            TwoFactorAuthenticationModel::class,
            'config',
        ],
        Model\RegisterModel::class                                        => [
            'FormElementManager',
            EntityManager::class,
            Model\Acl::class,
            Model\LoginModel::class,
            'config',
        ],
        Model\ForgotPasswordModel::class                                  => [
            'FormElementManager',
            EntityManager::class,
            PhpRenderer::class,
            'config',
        ],
        Model\GetClientIpAddress::class                                   => [
            'config',
        ],
        Model\TwoFactorAuthentication\Adapter\EmailAdapter::class         => [
            PhpRenderer::class,
        ],
        Model\TwoFactorAuthentication\Adapter\BulkSmsAdapter::class       => [
            PhpRenderer::class,
        ],

        /* Commands */
        Command\InitCommand::class       => [
            EntityManager::class,
            'config',
        ],
        Command\CreateUserCommand::class => [
            EntityManager::class,
            'config',
        ],
    ],
];
