<?php

namespace FwsDoctrineAuth\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use FwsDoctrineAuth\Model;
use Laminas\Mvc\I18n\Translator;

/**
 * IndexController
 *
 * @author Garry Childs <info@freedomwebservices.net>
 */
class IndexController extends AbstractActionController
{

    /**
     * @var Model\LoginModel
     */
    protected $loginModel;

    /**
     *
     * @var Model\RegisterModel
     */
    protected $registerModel;
    
    /**
     *
     * @var Translator 
     */
    protected $translator;

    public function __construct(Model\LoginModel $loginModel, Model\RegisterModel $registerModel, Translator $translator)
    {
        $this->loginModel = $loginModel;
        $this->registerModel = $registerModel;
        $this->translator = $translator;
    }

    /**
     * Redirect to login
     * 
     * @return ViewModel
     */
    public function indexAction()
    {
        return $this->redirect()->toRoute('doctrine-auth/default', array('action' => 'login'));
    }

    /**
     * Login user
     * 
     * @return ViewModel
     */
    public function loginAction()
    {
        $viewModel = new ViewModel();
        if ($this->getRequest()->isPost()) {
            if ($this->loginModel->processForm($this->getRequest()->getPost())) {
                if ($this->loginModel->login()) {
                    if ($this->loginModel->hasRedirect() && $this->loginModel->canRedirect()) {
                        return $this->redirect()->toUrl($this->loginModel->getRedirectUrl());
                    } else {
                        $redirect = $this->loginModel->getDefaultRedirect();
                        return $this->redirect()->toRoute($redirect['route'], $redirect['params'], $redirect['options']);
                    }                    
                } else {
                    $this->loginModel->getFormIdentityElement()->setMessages([$this->translator->translate('User not found')]);
                }
            }
        }

        $viewModel->form = $this->loginModel->getForm();
        return $viewModel;
    }

    public function logoutAction()
    {
        $this->loginModel->logout();
        return $this->redirect()->toRoute('doctrine-auth/default', array('action' => 'login'));
    }

    public function registerAction()
    {
        if ($this->registerModel->allowRegistration()) {
            $viewModel = new ViewModel();
            if ($this->getRequest()->isPost()) {
                if ($this->registerModel->processForm($this->getRequest()->getPost())) {
                    if ($this->registerModel->autoLogin()) {
                        if ($this->registerModel->login()) {
                            $redirect = $this->registerModel->getDefaultRedirect();
                            return $this->redirect()->toRoute($redirect['route'], $redirect['params'], $redirect['options']);
                        }
                    }
                    return $this->redirect()->toRoute('doctrine-auth/default', array('action' => 'login'));
                } else {
                    if ($this->registerModel->getForm()->isValid()) {
                        $viewModel->errorMessage = 'Unable to register you at this time, please try again later.';
                    } else {
                        $viewModel->errorMessage = 'There is a problem with the form you submitted, please correct errors highlighted.';
                    }
                }
            }
            $viewModel->form = $this->registerModel->getForm();
            return $viewModel;
        }
        return $this->redirect()->toRoute('doctrine-auth/default', array('action' => 'login'));
    }

}
