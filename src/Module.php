<?php

namespace FwsDoctrineAuth;

use Doctrine\ORM\EntityManagerInterface;
use FwsDoctrineAuth\Model\TwoFactorAuthentication\Adapter\AbstractAdapter;
use Laminas\EventManager\EventInterface;
use Laminas\ModuleManager\Feature\BootstrapListenerInterface;
use Laminas\EventManager\LazyListenerAggregate;
use Laminas\ModuleManager\ModuleManagerInterface;
use Laminas\Authentication\AuthenticationService;
use FwsDoctrineAuth\Command;
use Doctrine\ORM\Tools\Console\ConsoleRunner;
use FwsDoctrineAuth\Model\HashPassword;
use Symfony\Component\Console\Application;

class Module implements BootstrapListenerInterface
{

    /**
     * 
     * @param EventInterface $e
     */
    public function onBootstrap(EventInterface $e): void
    {
        $eventManager = $e->getApplication()->getEventManager();
        $serviceManager = $e->getApplication()->getServiceManager();

        $config = $serviceManager->get('config');

        /** Add lazy listeners from config */
        $aggregate = new LazyListenerAggregate(
                $config['event_manager']['lazy_listeners'], $serviceManager
        );
        $aggregate->attach($eventManager);

        HashPassword::setConfig($config);
    }

    /**
     * 
     * @return array
     */
    public function getConfig(): array
    {
        return include __DIR__ . '/../config/module.config.php';
    }

    /**
     * 
     * @return array
     */
    public function getServiceConfig(): array
    {
        return [
            'factories' => [
                AuthenticationService::class => function($serviceManager) {
                    return $serviceManager->get('doctrine.authenticationservice.orm_default');
                },
            ]
        ];
    }

    /**
     * Add doctrine cli command
     * @param ModuleManagerInterface $moduleManager
     */
    public function init(ModuleManagerInterface $moduleManager): void
    {
        $events = $moduleManager->getEventManager();

        $events->getSharedManager()->attach('doctrine', 'loadCli.post', function (EventInterface $event) {
            /* @var $cli Application */
            $cli = $event->getTarget();
            /* @var $entityManager EntityManagerInterface */
            $entityManager = $cli->getHelperSet()->get('em')->getEntityManager();
            $config = $event->getParam('ServiceManager')->get('config');
            ConsoleRunner::addCommands($cli);
            $cli->addCommands([
                new Command\InitCommand($entityManager, $config),
            ]);
        });
    }

}
