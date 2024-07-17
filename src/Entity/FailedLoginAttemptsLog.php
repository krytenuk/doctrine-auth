<?php

declare(strict_types=1);

/**
 * UserBlocked Entity
 **/

namespace FwsDoctrineAuth\Entity;

use DateTime;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use FwsDoctrineAuth\Entity\Repository\FailedLoginAttemptsLogRepository;

#[ORM\Entity(repositoryClass: FailedLoginAttemptsLogRepository::class, readOnly: false)]
#[ORM\Table(
    name: "login_attempts",
    options: [
        "collate" => "latin1_swedish_ci",
        "charset" => "latin1",
        "engine" => "InnoDB",
    ]
)]
class FailedLoginAttemptsLog implements EntityInterface
{
    #[ORM\Id,
        ORM\Column(
            name: "login_attempt_id",
            type: Types::INTEGER,
            nullable: false,
            options: ["unsigned" => true]
        ),
        ORM\GeneratedValue(strategy: "IDENTITY")
    ]
    private int|null $loginAttemptId = null;

    #[ORM\Column(
        name: "email_address",
        type: Types::STRING,
        length: 256,
        nullable: false
    )]
    private string|null $emailAddress;

    #[ORM\Column(
        name: "ip_address",
        type: Types::STRING,
        length: 16,
        nullable: false
    )]
    private string|null $ipAddress;

    #[ORM\Column(
        name: "date_logged",
        type: Types::DATETIME_MUTABLE,
        nullable: false,
    )]
    private DateTimeInterface $dateLogged;

    public function __construct()
    {
        $this->dateLogged = new DateTime();
    }

    /**
     * Get login attempt id
     */
    public function getLoginAttemptId(): int|null
    {
        return $this->loginAttemptId;
    }

    /**
     * Get email address entered
     */
    public function getEmailAddress(): string|null
    {
        return $this->emailAddress;
    }

    /**
     * Get IP address of login attempt
     */
    public function getIpAddress(): string|null
    {
        return $this->ipAddress;
    }

    /**
     * Get date logged
     */
    public function getDateLogged(): DateTimeInterface
    {
        return $this->dateLogged;
    }

    /**
     * Set the email address entered
     */
    public function setEmailAddress(string $emailAddress): FailedLoginAttemptsLog
    {
        $this->emailAddress = $emailAddress;
        return $this;
    }

    /**
     * Set the IP address of user
     */
    public function setIpAddress(string $ipAddress): FailedLoginAttemptsLog
    {
        $this->ipAddress = $ipAddress;
        return $this;
    }

    /**
     * Set date logged
     */
    public function setDateLogged(DateTimeInterface $dateLogged): FailedLoginAttemptsLog
    {
        $this->dateLogged = $dateLogged;
        return $this;
    }
}
