<?php

namespace FwsDoctrineAuth\Controller;

use FwsDoctrineAuth\Controller\Plugin\ValidateHash;
use FwsDoctrineAuth\Exception\DoctrineAuthException;
use FwsDoctrineAuth\Model\TwoFactorAuthentication\AppAuthenticationMethodModel;
use FwsDoctrineAuth\Model\TwoFactorAuthentication\ManageTwoFactorAuthenticationModel;
use FwsDoctrineAuth\Model\TwoFactorAuthentication\TwoFactorAuthenticationModel;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Mvc\Plugin\FlashMessenger\FlashMessenger;
use Laminas\View\Model\ViewModel;

/**
 * @method FlashMessenger flashMessenger()
 * @method ValidateHash validateHash(string $hash = null);
 */
class ManageTwoFactorAuthenticationController extends AbstractActionController
{

    use CheckHashTrait;

    /**
     * @param ManageTwoFactorAuthenticationModel $manage2FAMethodsModel
     */
    public function __construct(
        protected ManageTwoFactorAuthenticationModel $manage2FAMethodsModel
    )
    {
    }

    /**
     * @return ViewModel
     * @throws DoctrineAuthException
     */
    public function listMethodsAction(): ViewModel
    {
        $viewModel = new ViewModel();
        $viewModel->allowedMethods = $this->manage2FAMethodsModel->getAllowedAuthenticationMethods();
        $viewModel->user = $this->manage2FAMethodsModel->getUser();
        $viewModel->hash = $this->validateHash()->getHash();

        return $viewModel;
    }

    /**
     * @return Response
     * @throws DoctrineAuthException
     */
    public function addMethodAction(): Response
    {
        $method = $this->params()->fromRoute('method', null);
        if (!$method) {
            return $this->redirect()->toRoute('doctrine-auth/2fa/list-methods');
        }

        if (!$this->checkHash()) {
            return $this->redirect()->toRoute('doctrine-auth/2fa/list-methods');
        }

        if ($this->manage2FAMethodsModel->addMethod($method)) {
            $this->flashMessenger()->addSuccessMessage(
                sprintf(_('The %s authentication method has been added'),
                    $this->manage2FAMethodsModel->getMethodTitle()
                )
            );
            return $this->redirect()->toRoute('doctrine-auth/2fa/list-methods');
        }

        $this->flashMessenger()->addErrorMessage(_('Unable to add authentication method'));
        return $this->redirect()->toRoute('doctrine-auth/2fa/list-methods');
    }

    public function removeMethodAction(): Response
    {
        $method = $this->params()->fromRoute('method', null);
        if (!$method) {
            return $this->redirect()->toRoute('doctrine-auth/2fa/list-methods');
        }

        $viewModel = new ViewModel();
        if (!$this->checkHash()) {
            return $this->redirect()->toRoute('doctrine-auth/2fa/list-methods');
        }

        if ($this->manage2FAMethodsModel->removeMethod($method)) {
            $this->flashMessenger()->addSuccessMessage(
                $viewModel->message = sprintf(_('The %s authentication method has been removed'),
                    $this->manage2FAMethodsModel->getMethodTitle()
                )
            );
            return $this->redirect()->toRoute('doctrine-auth/2fa/list-methods');
        }

        $this->flashMessenger()->addErrorMessage(_('Unable to remove authentication method'));
        return $this->redirect()->toRoute('doctrine-auth/2fa/list-methods');
    }
}