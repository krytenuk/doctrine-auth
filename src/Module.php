<?php

namespace FwsDoctrineAuth;

use Laminas\EventManager\EventInterface;
use Laminas\ModuleManager\Feature\BootstrapListenerInterface;
use Laminas\EventManager\LazyListenerAggregate;
use Laminas\ModuleManager\ModuleManagerInterface;
use Laminas\Authentication\AuthenticationService;
use FwsDoctrineAuth\Command;
use Doctrine\ORM\Tools\Console\ConsoleRunner;
use FwsDoctrineAuth\Model\HashPassword;

class Module implements BootstrapListenerInterface
{

    /**
     * 
     * @param EventInterface $event
     */
    public function onBootstrap(EventInterface $event)
    {
        $eventManager = $event->getApplication()->getEventManager();
        $serviceManager = $event->getApplication()->getServiceManager();

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
    public function getConfig()
    {
        return include __DIR__ . '/../config/module.config.php';
    }

    /**
     * 
     * @return array
     */
    public function getServiceConfig()
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
    public function init(ModuleManagerInterface $moduleManager)
    {
        $events = $moduleManager->getEventManager()->getSharedManager();
        // Attach to helper set event and load the entity manager helper.
        $events->attach('doctrine', 'loadCli.post', function (EventInterface $event) {
            /* @var $cli \Symfony\Component\Console\Application */
            $cli = $event->getTarget();
            /* @var $entityManager \Doctrine\ORM\EntityManagerInterface */
            $entityManager = $cli->getHelperSet()->get('em')->getEntityManager();
            $config = $event->getParam('ServiceManager')->get('config');
            ConsoleRunner::addCommands($cli);
            $cli->addCommands([
                new Command\InitCommand($entityManager, $config),
                new Command\EncryptUsersCommand($entityManager, $config),
                new Command\DecryptUsersCommand($entityManager, $config),
                new Command\EncryptEntityCommand($entityManager, $config),
                new Command\DecryptEntityCommand($entityManager, $config),
            ]);
        });
    }

}
