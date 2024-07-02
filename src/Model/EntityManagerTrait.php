<?php

namespace FwsDoctrineAuth\Model;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\Persistence\Mapping\MappingException;
use Doctrine\Persistence\ObjectRepository;
use Exception;
use FwsDoctrineAuth\Entity\EntityInterface;

trait EntityManagerTrait
{
    protected EntityManagerInterface $entityManager;

    /**
     * @return EntityManagerInterface
     */
    public function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }

    /**
     * @param EntityManagerInterface $entityManager
     * @return EntityManagerTrait
     */
    public function setEntityManager(EntityManagerInterface $entityManager): static
    {
        $this->entityManager = $entityManager;
        return $this;
    }

    /**
     * Flush Doctrine Entity Manager
     * @param EntityManager $entityManager
     * @return boolean
     */
    protected function flushEntityManager(EntityManagerInterface $entityManager): bool
    {
        try {
            $entityManager->flush();
            return true;
        } catch (Exception) {
            $this->clearEntityManager($entityManager);
            return false;
        }
    }

    /**
     * Clear Doctrine entity manager
     * @param EntityManager $entityManager
     * @return bool
     */
    public function clearEntityManager(EntityManagerInterface $entityManager): bool
    {
        try {
            $entityManager->clear();
            return true;
        } catch (MappingException) {
            return false;
        }
    }

    /**
     * Set entity for removal from database in entity manager
     * @param EntityManager $entityManager
     * @param EntityInterface $entity
     * @return bool
     */
    public function removeEntity(EntityManagerInterface $entityManager, EntityInterface $entity): bool
    {
        try {
            $entityManager->remove($entity);
            return true;
        } catch (ORMException) {
            return false;
        }
    }

    /**
     * Add entity to entity manager
     * @param EntityManager $entityManager
     * @param EntityInterface $entity
     * @return bool
     */
    public function persistEntity(EntityManagerInterface $entityManager, EntityInterface $entity): bool
    {
        try {
            $entityManager->persist($entity);
            return true;
        } catch (ORMException) {
            return false;
        }
    }

    public function refresh(EntityManagerInterface $entityManager, $entity): void
    {
        $entityManager->refresh($entity);
    }
}