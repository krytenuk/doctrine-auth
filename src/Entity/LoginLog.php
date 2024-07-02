<?php

namespace FwsDoctrineAuth\Entity;

use Doctrine\ORM\Mapping as ORM;
use DateTimeInterface;
use DateTimeImmutable;

/**
 * LoginLog
 * @ORM\Entity
 * @ORM\Table(name="login_log", options={"collate"="latin1_swedish_ci", "charset"="latin1", "engine"="InnoDB"})
 * @author Garry Childs <info@freedomwebservices.net>
 */
class LoginLog implements EntityInterface
{

    /**
     * @var int|null
     * @ORM\Column(name="log_id", type="integer", options={"unsigned"=true}, nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private ?int $logId = null;

    /**
     * @var AuthUserInterface|null
     *
     * @ORM\ManyToOne(targetEntity="FwsDoctrineAuth\Entity\BaseUser", inversedBy="logins")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="user_id", referencedColumnName="user_id", onDelete="cascade")
     * })
     */
    private ?AuthUserInterface $user = null;

    /**
     * @var bool
     *
     * @ORM\Column(name="used_2fa", type="boolean", nullable=false, options={"default":0})
     */
    private bool $used2fa = false;

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
     * @return int|null
     */
    public function getLogId(): ?int
    {
        return $this->logId;
    }

    /**
     *
     * @return AuthUserInterface|null
     */
    public function getUser(): ?AuthUserInterface
    {
        return $this->user;
    }

    /**
     * 
     * @return bool
     */
    public function getUsed2fa(): bool
    {
        return $this->used2fa;
    }

    /**
     * 
     * @return DateTimeInterface
     */
    public function getDateLogged(): DateTimeInterface
    {
        return $this->dateLogged;
    }

    /**
     * 
     * @param AuthUserInterface $user
     * @return LoginLog
     */
    public function setUser(AuthUserInterface $user): LoginLog
    {
        $this->user = $user;
        return $this;
    }

    /**
     * 
     * @param bool $used2fa
     * @return LoginLog
     */
    public function setUsed2fa(bool $used2fa): LoginLog
    {
        $this->used2fa = $used2fa;
        return $this;
    }

    /**
     *
     * @param DateTimeInterface $dateLogged
     * @return LoginLog
     */
    public function setDateLogged(DateTimeInterface $dateLogged): LoginLog
    {
        $this->dateLogged = $dateLogged;
        return $this;
    }

}
