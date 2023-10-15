<?php

namespace FwsDoctrineAuth\Model;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\Persistence\Mapping\MappingException;
use Doctrine\Persistence\ObjectRepository;
use Exception;
use FwsDoctrineAuth\Entity\EntityInterface;

/**
 * AbstractModel
 *
 * @author Garry Childs <info@freedomwebservices.net>
 */
abstract class AbstractModel
{
    Use EntityManagerTrait;
}    