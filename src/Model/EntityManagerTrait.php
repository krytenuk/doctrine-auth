<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\Model;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\Persistence\Mapping\MappingException;
use Exception;
use FwsDoctrineAuth\Entity\EntityInterface;

trait EntityManagerTrait
{
    protected EntityManagerInterface $entityManager;

    public function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }

    /**
     * @return EntityManagerTrait
     */
    public function setEntityManager(EntityManagerInterface $entityManager): static
    {
        $this->entityManager = $entityManager;
        return $this;
    }

    /**
     * Flush Doctrine Entity Manager
     *
     * @param EntityManager $entityManager
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
     *
     * @param EntityManager $entityManager
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
     *
     * @param EntityManager $entityManager
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
     *
     * @param EntityManager $entityManager
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
