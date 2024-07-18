<?php
/**
 * Set layout for Doctrine Auth module if specified
 * @author Garry Childs <info@freedomwebservices.net>
 */

namespace FwsDoctrineAuth\Listener;

use Laminas\Mvc\MvcEvent;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class LayoutListener
{
    const AUTH_LAYOUT_MAP_KEY = 'fws-doctrine-auth/layout';

    /**
     * @param MvcEvent $event
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function setLayout(MvcEvent $event): void
    {
        $templateMapResolver = $event->getApplication()->getServiceManager()->get('ViewTemplateMapResolver');
        if (! $templateMapResolver->has(self::AUTH_LAYOUT_MAP_KEY)) {
            return;
        }

        $layoutViewModel = $event->getViewModel();

        // Rendering without layout?
        if ($layoutViewModel->terminate()) {
            return;
        }

        // Change template
        $layoutViewModel->setTemplate(self::AUTH_LAYOUT_MAP_KEY);
    }
}