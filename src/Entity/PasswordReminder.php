<?php

namespace FwsDoctrineAuth\Entity;

use Doctrine\ORM\Mapping as ORM;
use Application\Entity\Users;
use DateTime;

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
     * @var string
     * @ORM\Column(name="password_reminder_id", type="integer", length=13, nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $passwordReminderId;
    
    /**
     *
     * @var string
     * @ORM\Column(name="code", type="string", length=13, nullable=false, unique=true)
     */
    private $code;
    
    /**
     * @var Users
     *
     * @ORM\OneToOne(targetEntity="FwsDoctrineAuth\Entity\BaseUsers", inversedBy="passwordReminder")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="user_id", referencedColumnName="user_id", onDelete="cascade")
     * })
     */
    private $user;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="date_created", type="datetime", nullable=false)
     */
    private $dateCreated;
    
    /**
     * 
     * @return string
     */
    public function getPasswordReminderId()
    {
        return $this->passwordReminderId;
    }
    
    /**
     * 
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }
    
    /**
     * 
     * @return Users
     */
    public function getUser(): Users
    {
        return $this->user;
    }

    /**
     * 
     * @return DateTime
     */
    public function getDateCreated(): DateTime
    {
        return $this->dateCreated;
    }
    
    /**
     * 
     * @param string $code
     * @return $this
     */
    public function setCode($code)
    {
        $this->code = $code;
        return $this;
    }

    /**
     * 
     * @param Users $user
     * @return $this
     */
    public function setUser(Users $user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * 
     * @param DateTime $dateCreated
     * @return $this
     */
    public function setDateCreated(DateTime $dateCreated)
    {
        $this->dateCreated = $dateCreated;
        return $this;
    }
    
}
