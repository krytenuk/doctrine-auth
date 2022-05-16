<?php

namespace FwsDoctrineAuth\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Doctrine\ORM\EntityManagerInterface;
use FwsDoctrineAuth\Model\Crypt;
use FwsDoctrineAuth\Entity\BaseUsers;
use Laminas\Crypt\PublicKey\Rsa\Exception\RuntimeException;
use Exception;

/**
 * EncryptDatabaseCommand
 *
 * @author Garry Childs <info@freedomwebservices.net>
 */
class DecryptUsersCommand extends DecryptAbstractCommand
{

    /**
     * Setup CLI command
     */
    protected function configure()
    {
        parent::configure();
        $this->setName('doctrine-auth:decrypt-users')
                ->setDescription('Decrypt users data')
                ->setHelp(
                        <<<EOT
Decrypt sensitive user data on database
EOT
                )
                ->addArgument('additional-fields', InputArgument::IS_ARRAY, 'List of additional fields to encrypt')
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
        
        $this->fields = array_merge($input->getArgument('additional-fields'), ['mobileNumber']);

        $identityProperty = $this->config['doctrine']['authentication']['orm_default']['identity_property'];
        $key = array_search($identityProperty, $this->fields);
        if ($key !== false) {
            unset($this->fields[$key]);
            $output->writeln(sprintf('<error>The field %s is the identity property and should not be encrypted. This field has been removed.</error>', $identityProperty));
        }
        
        $credentialProperty = $this->config['doctrine']['authentication']['orm_default']['credential_property'];
        $key = array_search($credentialProperty, $this->fields);
        if ($key !== false) {
            unset($this->fields[$key]);
            $output->writeln(sprintf("<error>The field %s is the credential property, this is automatically encrypted and can't be decrypted. This field has been removed.</error>", $credentialProperty));
        }

        $users = $this->entityManager->getRepository($this->config['doctrine']['authentication']['orm_default']['identity_class'])->findAll();
        $this->processRecords($users);

        $this->outputSummary('decrypted', 'decrypting');

        return $this->saveEntity();
    }

}
