<?php

declare(strict_types=1);

namespace FwsDoctrineAuth\Command;

use Doctrine\ORM\EntityManagerInterface;
use FwsDoctrineAuth\Entity\AuthUserInterface;
use FwsDoctrineAuth\Entity\UserRole;
use Laminas\Crypt\Password\Bcrypt;
use Laminas\I18n\PhoneNumber\Validator\PhoneNumber;
use Laminas\Validator\EmailAddress;
use Laminas\Validator\Hostname;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function class_exists;
use function is_int;
use function is_numeric;
use function sprintf;

/**
 * ImportCommand
 */
class CreateUserCommand extends Command
{
    /** @var string */
    public static $defaultName = 'doctrine-auth:create-user';

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
            ->setDescription('Create a new auth user')
            ->setHelp(
                <<<EOT
Create a new auth user onto the database
EOT
            )
            ->addArgument('email', InputArgument::REQUIRED, 'The users email address')
            ->addArgument('password', InputArgument::REQUIRED, 'The users email address')
            ->addArgument('role', InputArgument::REQUIRED, 'The users role name or database id')
            ->addArgument('active', InputArgument::OPTIONAL, 'Is user active, true or false', 0)
            ->addArgument('mobile', InputArgument::OPTIONAL, 'Users mobile number', null);
    }

    /**
     * Add new user to database
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $role = $input->getArgument('role');
        if (is_numeric($role)) {
            $criteria = ['userRoleId' => (int) $role];
        } else {
            $criteria = ['role' => $role];
        }

        $userRole = $this->entityManager->getRepository(UserRole::class)->findOneBy($criteria);
        if (! $userRole instanceof UserRole) {
            $output->writeln(sprintf('<error>User role %s not found.</error>', is_int($role) ? "id $role" : $role));
            return Command::INVALID;
        }

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

        $active = (bool) $input->getArgument('active');

        $bcrypt = new Bcrypt();
        /** @var AuthUserInterface $user */
        $user = new $userClass();
        $user
            ->setEmailAddress($email)
            ->setPassword($bcrypt->create($input->getArgument('password')))
            ->setUserRole($userRole)
            ->setUserActive($active);

        $mobile = $input->getArgument('mobile');
        if ($mobile) {
            $phoneValidator = new PhoneNumber();
            if (! $phoneValidator->isValid($mobile)) {
                $output->writeln(sprintf(
                    '<error>Mobile number %s is not a valid telephone number format.</error>',
                    $mobile
                ));
                return Command::INVALID;
            }
            $user->setMobileNumber($mobile);
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();
        $output->writeln(sprintf('<info>Added new user %s, id #%d.</info>', $email, $user->getUserId()));

        return Command::SUCCESS;

        /* Roles already on database */
    }
}
