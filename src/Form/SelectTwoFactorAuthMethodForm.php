<?php

namespace FwsDoctrineAuth\Form;

use Laminas\Form\Form;
use Laminas\Session\Container;
use Laminas\Form\Element;
use FwsDoctrineAuth\Model\Select2faModel;

/**
 * SelectTwoFactorAuthMethodForm
 *
 * @author Garry Childs <info@freedomwebservices.net>
 */
class SelectTwoFactorAuthMethodForm extends Form
{
    
    /**
     * Authentication session storage container
     * @var Container
     */
    private Container $authContainer;
    
    /**
     * 
     * @param Container $authContainer
     */
    public function __construct(Container $authContainer)
    {
        parent::__construct('select-method');
        $this->authContainer = $authContainer;
        $this->setAttribute('method', 'post');
    }
    
    public function init(): void
    {
        $this->add([
            'name' => 'method',
            'type' => Element\Radio::class,
            'options' => [
                'label' => _('Select authentiction method'),
                'label_attributes' => ['class' => 'required'],
                'value_options' => $this->getMethodOptions(),
            ],
            
        ]);

        $this->add([
            'name' => 'csrf',
            'type' => Element\Csrf::class,
            'options' => [
                'csrf_options' => [
                    'timeout' => 600,
                ],
            ],
        ]);

        $this->add([
            'name' => 'submit',
            'type' => Element\Submit::class,
            'attributes' => [
                'value' => _('Select'),
                'id' => 'submitbutton',
                'label' => _('Select'),
            ],
        ]);
    }
    
    /**
     * Get 2FA methods array
     * @return array
     */
    private function getMethodOptions(): array
    {
        $methodsArray = [];
        if (!is_object($this->authContainer->identity) || !method_exists($this->authContainer->identity, 'getAuthMethods')) {
            return $methodsArray;
        }
        
        foreach ($this->authContainer->identity->getAuthMethods() as $method) {
            $methodsArray[$method->getMethod()] = Select2faModel::getMethodTitle($method->getMethod());
        }
        
        return $methodsArray;
    }
}
