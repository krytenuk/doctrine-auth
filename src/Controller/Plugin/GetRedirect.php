<?php

namespace FwsDoctrineAuth\Controller\Plugin;

use FwsDoctrineAuth\Entity\AuthUserInterface;
use FwsDoctrineAuth\Exception\DoctrineAuthException;
use FwsDoctrineAuth\Model\Acl;
use FwsDoctrineAuth\Model\AuthContainerStorage;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\Plugin\AbstractPlugin;

class GetRedirect extends AbstractPlugin
{
    /**
     * @param Acl $acl
     * @param AuthContainerStorage $authContainerStorage
     */
    public function __construct(
        protected Acl                  $acl,
        protected AuthContainerStorage $authContainerStorage
    )
    {
    }

    /**
     * @param AuthUserInterface $identity
     * @param bool $getDefault Return the default redirect
     * @return Response
     * @throws DoctrineAuthException
     */
    public function __invoke(AuthUserInterface $identity, bool $getDefault = false): Response
    {
        /**
         * HTTP 302 redirect if user is allowed to access resource
         */
        if ($this->hasRedirect() && $this->canRedirect($identity) && !$getDefault) {
            /* Redirect to requested page */
            return $this->getController()->plugin('redirect')->toUrl($this->getRedirectUrl());
        }

        /* Redirect to default page */
        $redirect = $this->getDefaultRedirect($identity);
        return $this->getController()->plugin('redirect')->toRoute($redirect['route'], $redirect['params'], $redirect['options']);
    }

    /**
     * Where to go if session container does not have redirect stored
     *
     * @param AuthUserInterface $userEntity
     * @return array
     * @throws DoctrineAuthException
     */
    private function getDefaultRedirect(AuthUserInterface $userEntity): array
    {
        $redirect = $this->acl->getRedirect($userEntity->getUserRole()->getRole());
        if ($redirect) {
            return $redirect;
        }
        throw new DoctrineAuthException('Unable to redirect, nowhere to go!');
    }

    /**
     * Determine if redirect exists
     * @return bool
     */
    private function hasRedirect(): bool
    {
        return (is_array($this->authContainerStorage->redirect ?? false));
    }

    /**
     * Can user go to redirect resource
     * @param AuthUserInterface $identity
     * @return bool
     */
    private function canRedirect(AuthUserInterface $identity): bool
    {
        return $this->acl->isAllowed(
            $identity->getUserRole()->getRole(),
            $this->authContainerStorage->redirect['controller'],
            $this->authContainerStorage->redirect['action']
        );
    }

    /**
     * Get url for redirect
     * @return string
     */
    private function getRedirectUrl(): string
    {
        $url = $this->authContainerStorage->redirect['url'];
        unset($this->authContainerStorage->redirect);
        return $url;
    }

}