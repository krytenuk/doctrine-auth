<?php

namespace FwsDoctrineAuth\Entity;

use Doctrine\ORM\Mapping as ORM;
use DateTimeInterface;
use DateTimeImmutable;

/**
 * PasswordReminder
 * @ORM\Entity
 * @ORM\Table(name="password_reminder", options={"collate"="latin1_swedish_ci", "charset"="latin1", "engine"="InnoDB"},
 *    uniqueConstraints={
 *        @ORM\UniqueConstraint(name="code", columns={"code"}),
 *        @ORM\UniqueConstraint(name="user_id", columns={"user_id"}),
 *    },
 * )
 * @author Garry Childs <info@freedomwebservices.net>
 */
class PasswordReminder implements EntityInterface
{

    /**
     * @var int|null
     * @ORM\Column(name="password_reminder_id", type="integer", options={"unsigned"=true}, nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private ?int $passwordReminderId = null;
    
    /**
     *
     * @var string
     * @ORM\Column(name="code", type="string", length=13, nullable=false, unique=true)
     */
    private string $code;
    
    /**
     * @var BaseUser
     *
     * @ORM\OneToOne(targetEntity="FwsDoctrineAuth\Entity\BaseUser", inversedBy="passwordReminder")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="user_id", referencedColumnName="user_id", onDelete="cascade")
     * })
     */
    private BaseUser $user;

    /**
     * @var DateTimeInterface
     *
     * @ORM\Column(name="date_created", type="datetime", nullable=false)
     */
    private DateTimeInterface $dateCreated;
    
    public function __construct()
    {
        $this->dateCreated = new DateTimeImmutable();
    }

    
    /**
     * 
     * @return int|null
     */
    public function getPasswordReminderId(): ?int
    {
        return $this->passwordReminderId;
    }
    
    /**
     * 
     * @return string|null
     */
    public function getCode(): ?string
    {
        return $this->code;
    }
    
    /**
     * 
     * @return BaseUser|null
     */
    public function getUser(): ?BaseUser
    {
        return $this->user;
    }

    /**
     * 
     * @return DateTimeInterface
     */
    public function getDateCreated(): DateTimeInterface
    {
        return $this->dateCreated;
    }
    
    /**
     * 
     * @param string $code
     * @return PasswordReminder
     */
    public function setCode(string $code): PasswordReminder
    {
        $this->code = $code;
        return $this;
    }

    /**
     * 
     * @param BaseUser $user
     * @return PasswordReminder
     */
    public function setUser(BaseUser $user): PasswordReminder
    {
        $this->user = $user;
        return $this;
    }

    /**
     * 
     * @param DateTimeInterface $dateCreated
     * @return PasswordReminder
     */
    public function setDateCreated(DateTimeInterface $dateCreated): PasswordReminder
    {
        $this->dateCreated = $dateCreated;
        return $this;
    }
    
}
