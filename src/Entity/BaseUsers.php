<?php

namespace FwsDoctrineAuth\Entity;

use Doctrine\ORM\Mapping as ORM;
use FwsDoctrineAuth\Entity\UserRoles;
use DateTime;

/**
 * Users
 * @ORM\Entity
 * @ORM\Table(name="users", options={"collate"="latin1_swedish_ci", "charset"="latin1", "engine"="InnoDB"},
 *    indexes={
 *        @ORM\Index(name="user_role_id", columns={"user_role_id"}),
 *    }
 * )
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @author Garry Childs <info@freedomwebservices.net>
 */
class BaseUsers implements EntityInterface
{

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $userId;

    /**
     * identity_property in config
     * @var string
     *
     * @ORM\Column(name="email_address", type="string", length=254, nullable=true)
     */
    private $emailAddress;

    /**
     * credential_property in config
     * @var string
     *
     * @ORM\Column(name="password", type="string", length=256, nullable=true)
     */
    private $password;
    /**
     * @var integer
     *
     * @ORM\Column(name="user_active", type="boolean", nullable=false, options={"default":0})
     */
    private $userActive;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="date_created", type="datetime")
     */
    private $dateCreated;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="date_modified", type="datetime")
     */
    private $dateModified;

    /**
     * @var BaseUserRoles
     *
     * @ORM\ManyToOne(targetEntity="FwsDoctrineAuth\Entity\UserRoles")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="user_role_id", referencedColumnName="user_role_id")
     * })
     */
    private $userRole;

    /**
     * @var PasswordReminder
     *
     * @ORM\OneToOne(targetEntity="FwsDoctrineAuth\Entity\PasswordReminder", cascade={"persist", "persist"}, mappedBy="user", orphanRemoval=true, cascade={"persist"})
     * })
     */
    private $passwordReminder;
    
    public function __construct()
    {
        $this->dateCreated = new DateTime();
        $this->dateModified = new DateTime();
        $this->userRole = new UserRoles();
    }

        /**
     * Get userId
     *
     * @return integer
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set emailAddress
     *
     * @param string $emailAddress
     * @return Users
     */
    public function setEmailAddress($emailAddress)
    {
        $this->emailAddress = $emailAddress;

        return $this;
    }

    /**
     * Get emailAddress
     *
     * @return string
     */
    public function getEmailAddress()
    {
        return $this->emailAddress;
    }

    /**
     * Set password
     *
     * @param string $password
     * @return Users
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

        /**
     * Get password
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }
    
    /**
     * 
     * @param integer $userActive
     * @return $this
     */
    public function setUserActive($userActive)
    {
        $this->userActive = $userActive;
        return $this;
    }

    /**
     * 
     * @return boolean
     */
    public function isUserActive()
    {
        return (bool) $this->userActive;
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
    
    /**
     * 
     * @return DateTime
     */
    public function getDateCreated()
    {
        return $this->dateCreated;
    }

    /**
     * 
     * @param DateTime $dateModified
     * @return $this
     */
    public function setDateModified(DateTime $dateModified)
    {
        $this->dateModified = $dateModified;
        return $this;
    }

    /**
     * 
     * @return DateTime
     */
    public function getDateModified()
    {
        return $this->dateModified;
    }

    /**
     * Set userRole
     *
     * @param BaseUserRoles $userRole
     * @return Users
     */
    public function setUserRole(UserRoles $userRole = NULL)
    {
        $this->userRole = $userRole;

        return $this;
    }

    /**
     * Get userRole
     *
     * @return BaseUserRoles
     */
    public function getUserRole()
    {
        return $this->userRole;
    }
    
    public function hasPasswordReminder()
    {
        return (bool) $this->getPasswordReminder();
    }

        /**
     * 
     * @return \FwsDoctrineAuth\Entity\PasswordReminder|NULL
     */
    public function getPasswordReminder()
    {
        return $this->passwordReminder;
    }

    /**
     * 
     * @param \FwsDoctrineAuth\Entity\PasswordReminder $passwordReminder
     * @return $this
     */
    public function setPasswordReminder(PasswordReminder $passwordReminder = NULL)
    {
        $this->passwordReminder = $passwordReminder;
        return $this;
    }


}
