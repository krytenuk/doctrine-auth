<?php

namespace FwsDoctrineAuth\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Doctrine\ORM\EntityManagerInterface;
use FwsDoctrineAuth\Entity\UserRoles;
use Exception;

/**
 * ImportCommand
 *
 * @author Garry Childs <info@freedomwebservices.net>
 */
class InitCommand extends Command
{

    /**
     *
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     *
     * @var array
     */
    protected $config;

    /**
     * 
     * @param EntityManagerInterface $entityManager
     * @param array $config
     */
    public function __construct(EntityManagerInterface $entityManager, Array $config)
    {
        $this->entityManager = $entityManager;
        $this->config = $config;
        parent::__construct();
    }

    /**
     * Setup CLI command
     */
    protected function configure()
    {
        parent::configure();
        $this->setName('doctrine-auth:init')
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
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        /* Roles already on database */
        if ($this->hasRoles() === true) {
            /* Truncate user roles table */
            if ($input->getOption('truncate')) {
                $connection = $this->entityManager->getConnection();
                $connection->executeQuery('SET FOREIGN_KEY_CHECKS = 0;');
                $platform = $connection->getDatabasePlatform();
                $tableName = $this->entityManager->getClassMetadata(UserRoles::class)->getTableName();
                $truncateSql = $platform->getTruncateTableSQL($tableName);
                $connection->executeUpdate($truncateSql);
                $connection->executeQuery('SET FOREIGN_KEY_CHECKS = 1;');
                $output->writeln('<info>User roles successfully truncated.</info>');
            }
        }
        $this->addRoles($output);
        return 1;
    }

    /**
     * User roles already on database
     * @return boolean
     */
    protected function hasRoles()
    {
        return (bool) $this->entityManager->getRepository(UserRoles::class)->countRoles();
    }

    /**
     * Add user roles to database
     * @param OutputInterface $output
     * @return boolean
     */
    protected function addRoles(OutputInterface $output)
    {
        /* No user roles in config */
        if (isset($this->config['doctrineAuthAcl']['roles']) === false || is_array($this->config['doctrineAuthAcl']['roles']) === false || empty($this->config['doctrineAuthAcl']['roles']) === true) {
            $output->writeln('<error>No user roles found in config</error>');
            return;
        }

        /* Process user roles from config */
        foreach ($this->config['doctrineAuthAcl']['roles'] as $role) {
            if ($this->entityManager->getRepository(UserRoles::class)->hasRole($role['id']) === true) {
                $output->writeln(sprintf('<comment>User role %s already exists, skipping.</comment>', $role['id']));
                continue;
            }
            $userRoleEntity = new UserRoles();
            $userRoleEntity->setRole($role['id']);
            $this->entityManager->persist($userRoleEntity);
            $output->writeln(sprintf('<info>Added user role %s.</info>', $role['id']));
        }
        
        try {
            $this->entityManager->flush();
            $output->writeln('<info>Finished processing user roles from config.</info>');
        } catch (Exception $exception) {
            $output->writeln('<error>Error writing to database.</error>');
        }
    }

}
