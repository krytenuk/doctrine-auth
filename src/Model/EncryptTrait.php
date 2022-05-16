<?php

namespace FwsDoctrineAuth\Model;

use FwsDoctrineAuth\Entity\EntityInterface;
use FwsDoctrineAuth\Model\Crypt;

/**
 *
 * @author Garry Childs <info@freedomwebservices.net>
 */
trait EncryptTrait
{
    
    /**
     * 
     * @var Crypt
     */
    protected Crypt $crypt;
    
    /**
     * 
     * @param Crypt $crypt
     * @return $this
     */
    public function setCrypt(Crypt $crypt)
    {
        $this->crypt = $crypt;
        return $this;
    }
    
    /**
     * 
     * @return Crypt
     */
    public function getCrypt(): Crypt
    {
        return $this->crypt;
    }
    
    /**
     * Encrypt specified entity fields
     * @param EntityInterface $entity
     * @param array $fields
     * @return EntityInterface
     */
    public function encryptFields(EntityInterface $entity, array $fields): EntityInterface
    {
        foreach ($fields as $field) {
            $getter = 'get' . ucfirst($field);
            $setter = 'set' . ucfirst($field);
            if (!method_exists($entity, $getter) || !method_exists($entity, $setter)) {
                continue;
            }
            
            if (empty($entity->$getter())) {
                continue;
            }
            
            $entity->$setter($this->crypt->rsaEncrypt($entity->$getter()));
        }
        return $entity;
    }
    
    /**
     * Decrypt specified entity fields
     * @param EntityInterface $entity
     * @param array $fields
     * @return EntityInterface
     */
    public function decryptFields(EntityInterface $entity, array $fields): EntityInterface
    {
        foreach ($fields as $field) {
            $getter = 'get' . ucfirst($field);
            $setter = 'set' . ucfirst($field);
            if (!method_exists($entity, $getter) || !method_exists($entity, $setter)) {
                continue;
            }
            
            if (empty($entity->$getter())) {
                continue;
            }
            
            $entity->$setter($this->crypt->rsaDecrypt($entity->$getter()));
        }
        return $entity;
    }

}
