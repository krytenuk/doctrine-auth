<?php

namespace FwsDoctrineAuth\Model;

use Doctrine\ORM\EntityManager;
use Exception;

/**
 * AbstractModel
 *
 * @author Garry Childs <info@freedomwebservices.net>
 */
abstract class AbstractModel
{

    /**
     * Flush Doctrine Entity Manager
     * @param EntityManager $entityManager
     * @return boolean
     */
    protected function flushEntityManager(EntityManager $entityManager)
    {
        try {
            $entityManager->flush();
        } catch (Exception $exception) {
            $this->clearEntityManager($entityManager);
            return FALSE;
        }
        return TRUE;
    }

    /**
     * Clear Doctrine entity manager
     * @param EntityManager $entityManager
     */
    public function clearEntityManager(EntityManager $entityManager)
    {
        $entityManager->clear();
    }
}    