<?php

namespace FwsDoctrineAuth\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Doctrine\ORM\EntityManagerInterface;
use FwsDoctrineAuth\Model\Crypt;
use FwsDoctrineAuth\Entity\EntityInterface;
use Doctrine\ORM\EntityRepository;
use Laminas\Crypt\PublicKey\Rsa\Exception\RuntimeException;
use Exception;

/**
 * Description of EncryptEntityCommand
 *
 * @author Garry Childs <info@freedomwebservices.net>
 */
class DecryptEntityCommand extends DecryptAbstractCommand
{

    /**
     * Setup CLI command
     */
    protected function configure()
    {
        parent::configure();
        $this->setName('doctrine-auth:decrypt-entity')
                ->setDescription('Decrypt given entitys data')
                ->setHelp(
                        <<<EOT
Encrypt sensitive data on database
EOT
                )
                ->addArgument('entity', InputArgument::REQUIRED, 'Entity class to decrypt, must be instance of FwsDoctrineAuth\Entity\EntityInterface')
                ->addArgument('fields', InputArgument::IS_ARRAY, 'List of fields (properties) to decrypt')
                ->addOption('dry-run', null, InputOption::VALUE_NONE, "Perform test run, don't save to database");
    }

    /**
     * Execute command
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->init($input, $output);
        
        $repository = $this->findEntityRepository($input->getArgument('entity'));
        if ($repository === null) {
            $output->writeln('<error>Entity repository not found.</error>');
            return 1;
        }
        
        $this->fields = $input->getArgument('fields');
        if (empty($this->fields)) {
            $output->writeln('No fields specified');
            return 2;
        }

        $this->processRecords($repository->findAll());

        $this->outputSummary('decrypted', 'decrypting');

        return $this->saveEntity();
    }

}
