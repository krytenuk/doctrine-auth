<?php

namespace FwsDoctrineAuth\Entity;

use Doctrine\ORM\Mapping as ORM;
use DateTimeInterface;
use DateTimeImmutable;

/**
 * UserBlocked
 * @ORM\Entity(repositoryClass="FwsDoctrineAuth\Entity\Repository\FailedLoginAttemptsLogRepository")
 * @ORM\Table(name="login_attempts", options={"collate"="latin1_swedish_ci", "charset"="latin1", "engine"="InnoDB"})
 * @author Garry Childs <info@freedomwebservices.net>
 */
class FailedLoginAttemptsLog implements EntityInterface
{
    /**
     * @var int|null
     * @ORM\Column(name="login_attempt_id", type="integer", options={"unsigned"=true}, nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private ?int $loginAttemptId = null;

    /**
     * @var string
     *
     * @ORM\Column(name="email_address", type="string", length=100, nullable=false)
     */
    private string $emailAddress;

    /**
     * @var string
     *
     * @ORM\Column(name="ip_address", type="string", length=16, nullable=false)
     */
    private string $ipAddress;

    /**
     * @var DateTimeInterface
     *
     * @ORM\Column(name="date_logged", type="datetime", nullable=false)
     */
    private DateTimeInterface $dateLogged;
    
    public function __construct()
    {
        $this->dateLogged = new DateTimeImmutable();
    }
    
    /**
     * 
     * @return int
     */
    public function getLoginAttemptId(): int
    {
        return $this->loginAttemptId;
    }

    /**
     * Get email address entered
     * @return string
     */
    public function getEmailAddress(): string
    {
        return $this->emailAddress;
    }

    /**
     * Get IP address of login attempt
     * @return string
     */
    public function getIpAddress(): string
    {
        return $this->ipAddress;
    }

    /**
     * Get date logged
     * @return DateTimeInterface
     */
    public function getDateLogged(): DateTimeInterface
    {
        return $this->dateLogged;
    }

    /**
     * Set the email address entered
     * @param string $emailAddress
     * @return FailedLoginAttemptsLog
     */
    public function setEmailAddress(string $emailAddress): FailedLoginAttemptsLog
    {
        $this->emailAddress = $emailAddress;
        return $this;
    }

    /**
     * Set the IP address of user
     * @param string $ipAddress
     * @return FailedLoginAttemptsLog
     */
    public function setIpAddress(string $ipAddress): FailedLoginAttemptsLog
    {
        $this->ipAddress = $ipAddress;
        return $this;
    }

    /**
     * Set date logged
     * @param DateTimeInterface $dateLogged
     * @return FailedLoginAttemptsLog
     */
    public function setDateLogged(DateTimeInterface $dateLogged): FailedLoginAttemptsLog
    {
        $this->dateLogged = $dateLogged;
        return $this;
    }

}
