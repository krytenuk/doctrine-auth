<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\Command;

use Doctrine\DBAL\Exception as DoctrineDBALException;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use FwsDoctrineAuth\Entity\UserRole;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function count;
use function in_array;
use function is_array;
use function sprintf;

/**
 * ImportCommand
 */
class InitCommand extends Command
{
    /** @var string */
    public static $defaultName = 'doctrine-auth:init';

    private array $userRoles = [];
    /**
     * @param array $config
     */
    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected array $config
    ) {
        parent::__construct();
    }

    /**
     * Setup CLI command
     */
    protected function configure(): void
    {
        parent::configure();
        $this->setName(self::$defaultName)
            ->setDescription('Initialize database data')
            ->setHelp(
                <<<EOT
Initialize the database with user roles from doctrine.auth.acl.local.php
EOT
            )
            ->addOption('truncate', 't', InputOption::VALUE_NONE, 'Truncate user roles table before adding roles from config');
    }

    /**
     * Add user roles to database
     *
     * @throws DoctrineDBALException
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->userRoles = $this->entityManager->getRepository(UserRole::class)->findAllArray();

        /* Roles already on database */
        if (count($this->userRoles)) {
            /* Truncate user roles table */
            if ($input->getOption('truncate')) {
                $connection = $this->entityManager->getConnection();
                $connection->executeQuery('SET FOREIGN_KEY_CHECKS = 0;');
                $platform    = $connection->getDatabasePlatform();
                $tableName   = $this->entityManager->getClassMetadata(UserRole::class)->getTableName();
                $truncateSql = $platform->getTruncateTableSQL($tableName);
                $connection->executeStatement($truncateSql);
                $connection->executeQuery('SET FOREIGN_KEY_CHECKS = 1;');
                $output->writeln('<info>User roles successfully truncated.</info>');
                $this->userRoles = [];
            }
        }
        $this->addRoles($output);
        return Command::SUCCESS;
    }

    /**
     * Add user roles to database
     */
    protected function addRoles(OutputInterface $output): bool
    {
        /* No user roles in config */
        $roles = $this->config['doctrineAuthAcl']['roles'] ?? null;
        if (! is_array($roles) || empty($roles)) {
            $output->writeln('<error>No user roles found in config</error>');
            return false;
        }

        /* Process user roles from config */
        foreach ($roles as $role) {
            if (in_array($role['id'], $this->userRoles)) {
                $output->writeln(sprintf('<comment>User role %s already exists, skipping.</comment>', $role['id']));
                continue;
            }
            $userRoleEntity = new UserRole();
            $userRoleEntity->setRole($role['id']);
            $this->entityManager->persist($userRoleEntity);
            $output->writeln(sprintf('<info>Added user role %s.</info>', $role['id']));
        }

        try {
            $this->entityManager->flush();
            $output->writeln('<info>Finished processing user roles from config.</info>');
            return true;
        } catch (Exception $exception) {
            $output->writeln(sprintf('<error>Error writing to database: %s.</error>', $exception->getMessage()));
        }
        return false;
    }
}
