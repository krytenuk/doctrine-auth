<?php

namespace FwsDoctrineAuth\Controller\Plugin;

use Doctrine\ORM\EntityManagerInterface;
use FwsDoctrineAuth\Entity\AuthUserInterface;
use FwsDoctrineAuth\Entity\BaseUser;
use FwsDoctrineAuth\Entity\LoginLog;
use FwsDoctrineAuth\Model\EntityManagerTrait;
use Laminas\Authentication\AuthenticationService;
use Laminas\Mvc\Controller\Plugin\AbstractPlugin;
use Laminas\Stdlib\ParametersInterface;

class LogSuccessfulLogin extends AbstractPlugin
{
    use EntityManagerTrait;

    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected AuthenticationService $authService
    )
    {
    }


    /**
     *
     * @param AuthUserInterface|null $identity
     * @param bool $used2fa
     * @return void
     */
    public function __invoke(AuthUserInterface|null $identity, bool $used2fa): void
    {
        if (!$identity) {
            return;
        }

        $loginLog = new LoginLog();
        $loginLog
            ->setUser($identity)
            ->setUsed2fa($used2fa);

        if (!$this->persistEntity($this->entityManager, $loginLog)) {
            return;
        }

        if ($this->flushEntityManager($this->entityManager)) {
            $identity->addLogin($loginLog);
            $this->authService->getStorage()->write($identity);
        }
    }
}