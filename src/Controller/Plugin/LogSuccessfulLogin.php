<?php

namespace FwsDoctrineAuth\Controller\Plugin;

use Doctrine\ORM\EntityManagerInterface;
use FwsDoctrineAuth\Entity\AuthUserInterface;
use FwsDoctrineAuth\Entity\BaseUser;
use FwsDoctrineAuth\Entity\LoginLog;
use FwsDoctrineAuth\Model\EntityManagerTrait;
use Laminas\Authentication\AuthenticationService;
use Laminas\Stdlib\ParametersInterface;

class LogSuccessfulLogin extends \Laminas\Mvc\Controller\Plugin\AbstractPlugin
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
     * @param AuthUserInterface $identity
     * @param bool $used2fa
     * @return void
     */
    public function __invoke(AuthUserInterface $identity, bool $used2fa): void
    {
        $loginLog = new LoginLog();
        $loginLog
            ->setUser($this->entityManager->getRepository(BaseUser::class)->findOneBy(['userId' => $identity->getUserId()]))
            ->setUsed2fa($used2fa);
        $this->persistEntity($this->entityManager, $loginLog);
        $this->flushEntityManager($this->entityManager);

        $identity->addLogin($loginLog);
        $this->authService->getStorage()->write($identity);
    }
}