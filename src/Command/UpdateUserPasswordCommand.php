<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\Command;

use Doctrine\ORM\EntityManagerInterface;
use FwsDoctrineAuth\Entity\AuthUserInterface;
use Laminas\Crypt\Password\Bcrypt;
use Laminas\Validator\EmailAddress;
use Laminas\Validator\Hostname;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function class_exists;
use function sprintf;

/**
 * ImportCommand
 */
class UpdateUserPasswordCommand extends Command
{
    /** @var string */
    public static $defaultName = 'doctrine-auth:update-user-password';

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
            ->setDescription('Update existing users password')
            ->setHelp(
                <<<EOT
Create a new auth user onto the database
EOT
            )
            ->addArgument('email', InputArgument::REQUIRED, 'The users email address')
            ->addArgument('password', InputArgument::REQUIRED, 'The users email address');
    }

    /**
     * Add new user to database
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $userClass = $this->config['doctrine']['authentication']['orm_default']['identity_class'] ?? null;
        if (! $userClass) {
            $output->writeln('<error>Identity class not set. Please check your Doctrine configuration.</error>');
            return Command::INVALID;
        }
        if (! class_exists($userClass)) {
            $output->writeln(sprintf(
                '<error>Identity class not %s not found. Please check your Doctrine configuration.</error>',
                $userClass
            ));
            return Command::INVALID;
        }

        $email = $input->getArgument('email');
        if ($this->entityManager->getRepository($userClass)->count(['emailAddress' => $email])) {
            $output->writeln(sprintf('<error>User email %s is already registered.</error>', $email));
            return Command::INVALID;
        }

        $emailValidator = new EmailAddress([
            'allow' => [
                Hostname::ALLOW_LOCAL,
            ],
        ]);
        if (! $emailValidator->isValid($email)) {
            $output->writeln(sprintf('<error>User email %s is not a valid email address format.</error>', $email));
            return Command::INVALID;
        }

        $bcrypt = new Bcrypt();
        /** @var AuthUserInterface $user */
        $user = $this->entityManager->getRepository($userClass)->findOneBy(['emailAddress' => $email]);
        if (! $user instanceof AuthUserInterface) {
            $output->writeln(sprintf('<error>User %s not found on database.</error>', $email));
            return Command::INVALID;
        }

        $user->setPassword($bcrypt->create($input->getArgument('password')));

        $this->entityManager->persist($user);
        $this->entityManager->flush();
        $output->writeln(sprintf('<info>Updated password for user %s, id #%d.</info>', $email, $user->getUserId()));

        return Command::SUCCESS;
    }
}
