<?php

namespace FwsDoctrineAuth\Command;

use FwsDoctrineAuth\Entity\EntityInterface;
use Laminas\Crypt\PublicKey\Rsa\Exception\RuntimeException;

/**
 * Description of EncryptAbstractCommand
 *
 * @author Garry Childs <info@freedomwebservices.net>
 */
abstract class DecryptAbstractCommand extends AbstractCommand
{

    /**
     * Decrypt entity fields (properties)
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

            $decrypted = $this->crypt->rsaDecrypt($entity->$getter());
            if ($decrypted === null) {
                $this->output->writeln(sprintf('<error>Field %s is not encrypted.</error>', $field));
                continue;
            }
            
            $entity->$setter($decrypted);
            $this->fieldsProcessed++;
            $this->verboseOutput(sprintf('<info>Field %s successfully decrypted.</info>', $field));
        }

        if (method_exists($entity, 'setEncrypted')) {
            $entity->setEncrypted(false);
        }
    }

}
