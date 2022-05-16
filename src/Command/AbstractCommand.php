<?php

namespace FwsDoctrineAuth\Command;

use Doctrine\ORM\EntityManagerInterface;
use FwsDoctrineAuth\Entity\EntityInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use FwsDoctrineAuth\Model\Crypt;
use Doctrine\ORM\EntityRepository;

/**
 * Description of AbstractCommand
 *
 * @author Garry Childs <info@freedomwebservices.net>
 */
abstract class AbstractCommand extends Command
{

    /**
     * 
     * @var InputInterface
     */
    protected InputInterface $input;

    /**
     * 
     * @var OutputInterface
     */
    protected OutputInterface $output;

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
     * # fields encrypted/decrypted
     * @var int
     */
    protected int $fieldsProcessed = 0;

    abstract protected function processFields(EntityInterface $entity);

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
     * Set input and output interfaces
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    protected function init(InputInterface $input, OutputInterface $output): void
    {
        $this->input = $input;
        $this->output = $output;
    }

    /**
     * Get entity repository
     * @param string $entityName
     * @return EntityRepository|null
     */
    protected function findEntityRepository(string $entityName): ?EntityRepository
    {
        if (class_exists($entityName)) {
            $repository = $this->entityManager->getRepository($entityName);
            if ($repository instanceof EntityRepository) {
                return $repository;
            }
        }
        
        $entity = null;
        foreach ($this->config['doctrine']['driver'] as $value) {
            if (array_key_exists('paths', $value) === false) {
                continue;
            }
            $entity = $this->getEntity($value, $entityName);
            if ($entity !== null) {
                break;
            }
        }

        if ($entity === null) {
            return null;
        }

        $repository = $this->entityManager->getRepository($entity);
        if ($repository instanceof EntityRepository === false) {
            return null;
        }
        return $repository;
    }

    /**
     * Get entity FQCN
     * @param array $config
     * @param string $entityName
     * @return string|null
     */
    private function getEntity(array $config, string $entityName): ?string
    {
        foreach ($config['paths'] as $path) {
            $file = $path . '/' . ucfirst($entityName) . '.php';
            if (file_exists($file) === false) {
                continue;
            }
            $classes = get_declared_classes();
            include $file;
            $diff = array_diff(get_declared_classes(), $classes);
            return reset($diff);
        }
        return null;
    }

    /**
     * Process records from database
     * @param array $records
     * @return void
     */
    protected function processRecords(array $records): void
    {
        foreach ($records as $key => $record) {
            $this->verboseOutput(sprintf('<comment>Processing record %d.</comment>', $key));
            $this->records++;

            $this->processFields($record);

            $this->entityManager->persist($record);
        }
    }

    /**
     * Save entity to database if not in --dry-run mode
     * @return int
     */
    protected function saveEntity(): int
    {
        if ($this->input->getOption('dry-run')) {
            $this->output->writeln(sprintf('<comment>--dry-run, %s not been saved.</comment>', $this->records < 2 ? 'entity has' : 'entities have'));
            return 0;
        }

        try {
            $this->entityManager->flush();
            $this->output->writeln('<info>Entity has been saved to database.</info>');
        } catch (Exception $exception) {
            $this->output->writeln(sprintf('<error>Error writing to database: %s.</error>', $exception->getMessage()));
            return 3;
        }
        return 0;
    }

    /**
     * Write to console if in verbose mode (-v, -vv or -vvv)
     * @param string|array $message
     * @return void
     */
    protected function verboseOutput($message): void
    {
        if ($this->input->getOption('verbose')) {
            $this->output->writeln($message);
        }
    }

    /**
     * Write summary to console
     * @param string $encryptDecrypt
     */
    protected function outputSummary(string $encryptDecrypt, string $encryptingDecrypting)
    {
        $this->verboseOutput('');
        $this->output->writeln([
            sprintf('<info>%d records found.</info>', $this->records),
            sprintf('<info>%d fields %s.</info>', $this->fieldsProcessed, $encryptDecrypt),
        ]);

        $this->output->writeln(sprintf('<info>Finished %s data.</info>', $encryptingDecrypting));
    }

}
