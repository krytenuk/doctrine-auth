<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\Event;

use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * A small dispatcher, as Mezzio does not include one. Listeners come from the doctrine-auth.listeners config:
 *
 *     LoginFailed::class => ['my-key' => MyListener::class]
 *
 * Each listener is a callable service. Set a key to null to remove a listener. To use your own PSR-14 dispatcher,
 * replace this class's service.
 */
final class AuthEventDispatcher implements EventDispatcherInterface
{
    /**
     * @param array<class-string, array<string, string|null>> $listeners
     */
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly array $listeners,
    ) {
    }

    public function dispatch(object $event): object
    {
        foreach ($this->listeners[$event::class] ?? [] as $listener) {
            if ($listener !== null) {
                ($this->container->get($listener))($event);
            }
        }

        return $event;
    }
}
