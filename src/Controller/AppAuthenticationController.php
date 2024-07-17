<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\Controller;

use FwsDoctrineAuth\Controller\Plugin\ValidateHash;
use FwsDoctrineAuth\Exception\DoctrineAuthException;
use FwsDoctrineAuth\Model\TwoFactorAuthentication\AppAuthenticationMethodModel;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

use function _;

/**
 * @method ValidateHash validateHash(string $hash = null);
 */
class AppAuthenticationController extends AbstractActionController
{
    use CheckHashTrait;

    public function __construct(
        protected AppAuthenticationMethodModel $appAuthenticationMethodModel
    ) {
    }

    /**
     * @throws DoctrineAuthException
     */
    public function addAppAuthenticationMethodAction(): Response|ViewModel
    {
        $viewModel               = new ViewModel([
            'authCodeForm' => $this->appAuthenticationMethodModel->getAuthCodeForm(),
            'qrCode' => $this->appAuthenticationMethodModel->getQrCode(),
            'hash' => $this->validateHash()->getHash(),
        ]);

        if (! $this->getRequest()->isPost()) {
            if (! $this->checkHash()) {
                return $this->redirect()->toRoute('doctrine-auth/2fa/list-methods');
            }
            return $viewModel;
        }

        $postData = $this->getRequest()->getPost();
        if (
            ! (
            $this->appAuthenticationMethodModel->getTwoFactorAuthenticationModel()->processAuthForm($postData) &&
            $this->appAuthenticationMethodModel->getTwoFactorAuthenticationModel()->authenticate()
            )
        ) {
            $viewModel->authCodeForm->get('code')->setMessages([_('There is a problem with the code you entered')]);
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
