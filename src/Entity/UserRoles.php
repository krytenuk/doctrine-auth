<?php
namespace FwsDoctrineAuth\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserRoles
 * @ORM\Entity(repositoryClass="FwsDoctrineAuth\Entity\Repository\UserRolesRepository")
 * @ORM\Table(name="user_roles", 
 * options={"collate"="latin1_swedish_ci", "charset"="latin1", "engine"="InnoDB"}, 
 * uniqueConstraints={@ORM\UniqueConstraint(name="role_idx", columns={"role"})}
 * )
 * @author Garry Childs <info@freedomwebservices.net>
 */
class UserRoles implements EntityInterface
{

    /**
     * @var integer
     *
     * @ORM\Column(name="user_role_id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $userRoleId;

    /**
     * @var string
     *
     * @ORM\Column(name="role", type="string", length=100, nullable=false)
     */
    private $role;



    /**
     * Get userRoleId
     *
     * @return integer
     */
    public function getUserRoleId()
    {
        return $this->userRoleId;
    }

    /**
     * Set role
     *
     * @param string $role
     * @return \Application\Entity\UserRoles
     */
    public function setRole($role)
    {
        $this->role = $role;

        return $this;
    }

    /**
     * Get role
     *
     * @return string
     */
    public function getRole()
    {
        return $this->role;
    }
}
