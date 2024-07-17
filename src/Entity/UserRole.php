<?php

/**
 * UserRole
 */

declare(strict_types=1);

namespace FwsDoctrineAuth\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use FwsDoctrineAuth\Entity\Repository\UserRoleRepository;

#[ORM\Entity(repositoryClass: UserRoleRepository::class, readOnly: false)]
#[ORM\Table(
    name: "user_roles",
    options: [
        "collate" => "latin1_swedish_ci",
        "charset" => "latin1",
        "engine" => "InnoDB",
    ]
)]
#[ORM\UniqueConstraint(name: "role", columns: ["role"])]
class UserRole implements EntityInterface
{
    #[ORM\Id,
        ORM\Column(
            name: "user_role_id",
            type: Types::INTEGER,
            nullable: false,
            options: ["unsigned" => true]
        ),
        ORM\GeneratedValue(strategy: "IDENTITY")
    ]
    private int|null $userRoleId;

    #[ORM\Column(
        name: "role",
        type: Types::STRING,
        length: 100,
        unique: true,
        nullable: false
    )]
    private string|null $role;

    /**
     * Set user role id
     */
    public function setUserRoleId(int $userRoleId): UserRole
    {
        $this->userRoleId = $userRoleId;
        return $this;
    }

    /**
     * Get user role id
     */
    public function getUserRoleId(): int|null
    {
        return $this->userRoleId;
    }

    /**
     * Set user role
     */
    public function setRole(string $role): UserRole
    {
        $this->role = $role;
        return $this;
    }

    /**
     * Get user role
     */
    public function getRole(): string|null
    {
        return $this->role;
    }
}
