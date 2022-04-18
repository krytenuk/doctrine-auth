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
class DecryptUsersCommand extends Command
{

    /**
     *
     * @var EntityManagerInterface
     */
    protected EntityManagerInterface $entityManager;

    /**
     * 
     * @var Crypt
     */
    protected Crypt $crypt;

    /**
     * Laminas config
     * @var array
     */
    protected array $config;

    /**
     * Fields to encrypt
     * @var array
     */
    protected array $fields;

    /**
     * # records found
     * @var int
     */
    protected int $records = 0;

    /**
     * # fields encrypted
     * @var int
     */
    protected int $fieldsEncrypted = 0;

    /**
     * 
     * @param EntityManagerInterface $entityManager
     * @param array $config
     */
    public function __construct(EntityManagerInterface $entityManager, Array $config)
    {
        $this->entityManager = $entityManager;
        $this->config = $config;
        $this->crypt = new Crypt($config);
        parent::__construct();
    }

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

    public function execute(InputInterface $input, OutputInterface $output)
    {
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
        foreach ($users as $user) {
            $this->verboseOutput($input, $output, sprintf('<comment>Processing user id#%d.</comment>', $user->getUserId()));

            $this->records++;

            if ($user->isEncrypted() === false) {
                $this->verboseOutput($input, $output, '<error>User data encrypted flag is not set.</error>');
            }

            $this->processFields($user, $input, $output);
            $user->setEncrypted(false);
            $this->entityManager->persist($user);
        }

        $this->verboseOutput($input, $output, '');
        $output->writeln([
            sprintf('<info>%d records found.</info>', $this->records),
            sprintf('<info>%d fields decrypted.</info>', $this->fieldsEncrypted),
        ]);

        $output->writeln('<info>Finished decrypting data.</info>');

        if ($input->getOption('dry-run')) {
            return 1;
        }
        try {
            $this->entityManager->flush();
        } catch (Exception $exception) {
            $output->writeln(sprintf('<error>Error writing to database: %s.</error>', $exception->getMessage()));
        }
        return 1;
    }

    protected function processFields(BaseUsers $user, InputInterface $input, OutputInterface $output): void
    {
        foreach ($this->fields as $field) {
            $getter = 'get' . ucfirst($field);
            $setter = 'set' . ucfirst($field);
            if (method_exists($user, $getter) === false) {
                $output->writeln(sprintf('<error>Method %s not found in %s.</error>', $getter, get_class($user)));
                continue;
            }

            if (!$user->$getter()) {
                continue;
            }

            try {
                $decrypted = $this->crypt->rsaDecrypt($user->$getter());
                $user->$setter($decrypted);
                $this->fieldsEncrypted++;
                $this->verboseOutput($input, $output, sprintf('<info>Field %s successfully decrypted.</info>', $field));
            } catch (RuntimeException $exception) {
                $output->writeln(sprintf('<error>Field %s is not encrypted.</error>', $field));
            }
        }
    }

    /**
     * 
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param string|array $message
     */
    protected function verboseOutput(InputInterface $input, OutputInterface $output, $message)
    {
        if ($input->getOption('verbose')) {
            $output->writeln($message);
        }
    }

}
