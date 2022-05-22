<?php

namespace FwsDoctrineAuth\Command;

use FwsDoctrineAuth\Entity\EntityInterface;
use Laminas\Crypt\PublicKey\Rsa\Exception\RuntimeException;

/**
 * Description of EncryptAbstractCommand
 *
 * @author Garry Childs <info@freedomwebservices.net>
 */
abstract class EncryptAbstractCommand extends AbstractCommand
{

    /**
     * Encrypt entity fields (properties)
     * @param EntityInterface $entity
     * @return void
     */
    protected function processFields(EntityInterface $entity): void
    {
        foreach ($this->fields as $field) {
            $getter = 'get' . ucfirst($field);
            $setter = 'set' . ucfirst($field);
            if (method_exists($entity, $getter) === false) {
                $this->output->writeln(sprintf('<error>Method %s not found in %s.</error>', $getter, get_class($entity)));
                continue;
            }
            if (method_exists($entity, $setter) === false) {
                $this->output->writeln(sprintf('<error>Method %s not found in %s.</error>', $setter, get_class($entity)));
                continue;
            }

            if (!$entity->$getter()) {
                continue;
            }

            $crypt = new Crypt($this->config);
            if ($crypt->rsaDecrypt($entity->$getter()) !== null) {
                $this->output->writeln(sprintf('<error>Field %s is already encrypted.</error>', $field));
                continue;
            }
            $encrypted = $crypt->rsaEncrypt($entity->$getter());
            if ($encrypted === null) {
                $this->output->writeln(sprintf('<error>Unable to encrypt field %s.</error>', $field));
                continue;
            }
            $entity->$setter($encrypted);
            $this->fieldsProcessed++;
            $this->verboseOutput(sprintf('<info>Field %s successfully encrypted.</info>', $field));
        }

        if (method_exists($entity, 'setEncrypted')) {
            $entity->setEncrypted(true);
        }
    }

}
