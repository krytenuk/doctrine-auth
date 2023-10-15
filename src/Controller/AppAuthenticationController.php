<?php

namespace FwsDoctrineAuth\Controller;

use FwsDoctrineAuth\Controller\Plugin\ValidateHash;
use FwsDoctrineAuth\Exception\DoctrineAuthException;
use FwsDoctrineAuth\Model\TwoFactorAuthentication\AppAuthenticationMethodModel;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

/**
 * @method ValidateHash validateHash(string $hash = null);
 */
class AppAuthenticationController extends AbstractActionController
{


    /**
     * @param AppAuthenticationMethodModel $appAuthenticationMethodModel
     */
    public function __construct(
        protected AppAuthenticationMethodModel       $appAuthenticationMethodModel
    )
    {
    }


    /**
     * @throws DoctrineAuthException
     */
    public function addAppAuthenticationMethodAction(): Response|ViewModel
    {
        $viewModel = new ViewModel();
        $viewModel->authCodeForm = $this->appAuthenticationMethodModel->getAuthCodeForm();
        $viewModel->qrCode = $this->appAuthenticationMethodModel->getQrCode();
        $viewModel->hash = $this->validateHash()->getHash();

        if (!$this->getRequest()->isPost()) {
            if (!$this->checkHash()) {
                return $this->redirect()->toRoute('doctrine-auth/2fa/list-methods');
            }
            return $viewModel;
        }

        $postData = $this->getRequest()->getPost();
        if (!(
            $this->appAuthenticationMethodModel->getTwoFactorAuthenticationModel()->processAuthForm($postData) &&
            $this->appAuthenticationMethodModel->getTwoFactorAuthenticationModel()->authenticate()
        )) {
            $this->appAuthenticationMethodModel->getAuthCodeForm()->get('code')->setMessages([_('There is a problem with the code you entered')]);
            return $viewModel;
        }

        $this->appAuthenticationMethodModel->addMethod();
        return $this->redirect()->toRoute('doctrine-auth/2fa/list-methods');
    }

    public function regenerateAppSecretAction(): Response
    {
        $this->appAuthenticationMethodModel->getSecret(true);
        return $this->redirect()->toRoute('doctrine-auth/2fa/add-app-method', ['hash' => $this->validateHash()->getHash()]);
    }
}