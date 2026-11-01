<?php

declare(strict_types=1);

namespace FwsDoctrineAuthTest\Event;

use FwsDoctrineAuth\Event\AuthEventDispatcher;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\TestCase;
use stdClass;

final class AuthEventDispatcherTest extends TestCase
{
    public function testCallsListenersAndSkipsRemovedOnes(): void
    {
        $called    = [];
        $container = new ServiceManager(['services' => [
            'first' => static function (stdClass $event) use (&$called): void {
                $called[] = 'first';
            },
        ]]);

        $dispatcher = new AuthEventDispatcher($container, [stdClass::class => ['one' => 'first', 'two' => null]]);
        $event      = new stdClass();

        self::assertSame($event, $dispatcher->dispatch($event));
        self::assertSame(['first'], $called);
    }

    public function testEventsWithoutListenersAreFine(): void
    {
        $event = new stdClass();

        self::assertSame($event, (new AuthEventDispatcher(new ServiceManager(), []))->dispatch($event));
    }
}
