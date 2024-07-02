<?php

namespace FwsDoctrineAuth\Controller;

use FwsDoctrineAuth\Entity\AuthUserInterface;
use FwsDoctrineAuth\Exception\DoctrineAuthException;
use FwsDoctrineAuth\Model\TwoFactorAuthentication\TwoFactorAuthenticationModel;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;


/**
 * TwoFactorAuthenticationController
 *
 * @author Garry Childs <info@freedomwebservices.net>
 *
 * @method string translate($message, $textDomain = null, $locale = null)
 * @method Response getAuthRedirect(AuthUserInterface $identity, bool $getDefault = false)
 * @method bool isIpBlocked()
 * @method bool blockIpAddress(string $emailEntered)
 * @method bool logFailedLoginAttempt(string $emailAddress)
 * @method bool logSuccessfulLogin(AuthUserInterface $identity, bool $used2fa)
 */
class TwoFactorAuthenticationController extends AbstractActionController
{
    /**
     * @param TwoFactorAuthenticationModel $twoFactorAuthModel
     */
    public function __construct(
        protected TwoFactorAuthenticationModel $twoFactorAuthModel
    )
    {}

    /**
     * Let the user choose their 2FA method
     * @return Response|ViewModel
     */
    public function selectAuthMethodAction(): Response|ViewModel
    {
        if (!$this->twoFactorAuthModel->getIdentity()) {
            return $this->redirect()->toRoute('doctrine-auth/login');
        }

        $viewModel = new ViewModel();
        $viewModel->userAuthMethodsForm = $this->twoFactorAuthModel->getSelectAuthMethodForm();

        $authMethod = $this->twoFactorAuthModel->getSingleAuthMethod();
        if ($authMethod) {
            if (!$this->twoFactorAuthModel->setSelectedAuthMethod($authMethod->getMethod())) {
                $viewModel->userAuthMethodsForm->get('method')->setMessages([_('Authentication method not found')]);
                $this->getResponse()->setStatusCode(Response::STATUS_CODE_403);
                return $viewModel;
            }
            return $this->redirect()->toRoute('doctrine-auth/2fa/authenticate');
        }

        if (!$this->getRequest()->isPost()) {
            return $viewModel;
        }

        $postData = $this->getRequest()->getPost();
        if (!$this->twoFactorAuthModel->processSelectForm($postData)) {
            $viewModel->userAuthMethodsForm->get('method')->setMessages([_('You must select your authentication method')]);
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_400);
            return $viewModel;
        }

        if (!$this->twoFactorAuthModel->getSelectedAuthMethod()) {
            $viewModel->userAuthMethodsForm->get('method')->setMessages([_('Authentication method not found')]);
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_400);
            return $viewModel;
        }

        return $this->redirect()->toRoute('doctrine-auth/2fa/authenticate');
    }

    /**
     * 2FA authenticate user
     * @return Response|ViewModel
     * @throws DoctrineAuthException
     */
    public function authenticateAction(): Response|ViewModel
    {
        $identity = $this->twoFactorAuthModel->getIdentity();
        if (!$identity) {
            return $this->redirect()->toRoute('doctrine-auth/login');
        }

        $viewModel = new ViewModel();
        $viewModel->setTemplate($this->twoFactorAuthModel->getAuthenticateTemplate());
        $viewModel->authCodeForm = $this->twoFactorAuthModel->getAuthCodeForm();
        $viewModel->user = $identity;
        $viewModel->authMethod = $this->twoFactorAuthModel->getSelectedAuthMethod();

        if (!$this->getRequest()->isPost()) {
            $viewModel->codeSent = true;
            if (!$this->twoFactorAuthModel->codeSent()) {
                $this->twoFactorAuthModel->generateCode();
                $viewModel->codeSent = $this->twoFactorAuthModel->sendCode();
                $viewModel->adaptorErrors = $this->twoFactorAuthModel->getAdaptor()->getErrors();
            }
            return $viewModel;
        }

        $codeElement = $viewModel->authCodeForm->get('code');
        if ($this->isIpBlocked()) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_401);
            $codeElement->setMessages([_('Sorry your IP address is blocked')]);
            return $viewModel;
        }

        $postData = $this->getRequest()->getPost();
        if (!$this->twoFactorAuthModel->processAuthForm($postData)) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_400);
            $codeElement->setMessages([_('There is a problem with the code you entered')]);
            return $viewModel;
        }

        if ($this->twoFactorAuthModel->codeExpired()) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_401);
            $this->twoFactorAuthModel->generateCode();
            $viewModel->codeSent = $this->twoFactorAuthModel->sendCode();
            if ($viewModel->codeSent) {
                $codeElement->setMessages([_('Your code has expired, a new code has been sent')]);
                return $viewModel;
            }
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_500);
            $viewModel->adaptorErrors = $this->twoFactorAuthModel->getAdaptor()->getErrors();
            return $viewModel;
        }

        if ($this->twoFactorAuthModel->authenticate()) {
            $identity = $this->twoFactorAuthModel->getIdentity();
            $this->twoFactorAuthModel->storeIdentity($identity);
            $this->logSuccessfulLogin($identity, true);
            return $this->getAuthRedirect($identity);
        }

        $this->getResponse()->setStatusCode(Response::STATUS_CODE_401);
        $emailAddress = $this->twoFactorAuthModel->getIdentity()->getEmailAddress();
        if ($this->logFailedLoginAttempt($emailAddress)) {
            if ($this->blockIpAddress($emailAddress)) {
                $codeElement->setMessages(['Sorry your IP address is blocked']);
                return $viewModel;
            }
        }
        $codeElement->setMessages([_('Incorrect code entered')]);
        return $viewModel;
    }

    /**
     * Resend 2FA code
     * @return Response
     * @throws DoctrineAuthException
     */
    public function resendCodeAction(): Response
    {
        $this->twoFactorAuthModel->sendCode();
        return $this->redirect()->toRoute('doctrine-auth/2fa/authenticate');
    }

}