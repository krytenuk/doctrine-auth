<?php
namespace FwsDoctrineAuth\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserRoles
 * @ORM\Entity(repositoryClass="FwsDoctrineAuth\Entity\Repository\UserRolesRepository")
 * @ORM\Table(name="user_roles", 
 *    options={
 *        "collate"="latin1_swedish_ci", 
 *        "charset"="latin1", 
 *        "engine"="InnoDB"
 *    }, 
 *    uniqueConstraints={
 *        @ORM\UniqueConstraint(name="role", columns={"role"})
 *    }
 * )
 * @author Garry Childs <info@freedomwebservices.net>
 */
class UserRoles implements EntityInterface
{

    /**
     * @var int|null
     *
     * @ORM\Column(name="user_role_id", type="integer", options={"unsigned"=true}, nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private int $userRoleId;

    /**
     * @var string
     *
     * @ORM\Column(name="role", type="string", length=100, nullable=false)
     */
    private string $role;

    /**
     * Set user role id
     * @param int $userRoleId
     * @return UserRoles
     */
    public function setUserRoleId(int $userRoleId): UserRoles
    {
        $this->userRoleId = $userRoleId;
        return $this;
    }

    /**
     * Get user role id
     *
     * @return int|null
     */
    public function getUserRoleId(): ?int
    {
        return $this->userRoleId;
    }

    /**
     * Set role
     *
     * @param string $role
     * @return UserRoles
     */
    public function setRole(string $role): UserRoles
    {
        $this->role = $role;
        return $this;
    }

    /**
     * Get role
     *
     * @return string|null
     */
    public function getRole(): ?string
    {
        return $this->role;
    }
    
}
