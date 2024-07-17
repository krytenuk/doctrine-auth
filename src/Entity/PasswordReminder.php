<?php

/**
 * PasswordReminder Entity
 */

declare(strict_types=1);

namespace FwsDoctrineAuth\Entity;

use DateTime;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(readOnly: false)]
#[ORM\Table(
    name: "password_reminder",
    options: [
        "collate" => "latin1_swedish_ci",
        "charset" => "latin1",
        "engine" => "InnoDB",
    ],
)]
#[ORM\UniqueConstraint(name: "code", columns: ["code"])]
#[ORM\UniqueConstraint(name: "user_id", columns: ["user_id"])]
class PasswordReminder implements EntityInterface
{
    #[ORM\Id,
        ORM\Column(
            name: "password_reminder_id",
            type: Types::INTEGER,
            nullable: false,
            options: ["unsigned" => true]
        ),
        ORM\GeneratedValue(strategy: "IDENTITY")
    ]
    private int|null $passwordReminderId = null;

    #[ORM\Column(
        name: "code",
        type: Types::STRING,
        length: 13,
        unique: true,
        nullable: false
    )]
    private string|null $code;

    #[ORM\OneToOne(
        inversedBy: "passwordReminder",
        targetEntity: BaseUser::class
    )]
    #[ORM\JoinColumn(
        name: "user_id",
        referencedColumnName: "user_id",
        unique: true,
        nullable: false,
        onDelete: "CASCADE"
    )]
    private BaseUser|null $user;

    #[ORM\Column(
        name: "date_created",
        type: Types::DATETIME_MUTABLE,
        nullable: false,
    )]
    protected DateTimeInterface|null $dateCreated;

    public function __construct()
    {
        $this->dateCreated = new DateTime();
    }

    public function getPasswordReminderId(): int|null
    {
        return $this->passwordReminderId;
    }

    public function getCode(): string|null
    {
        return $this->code;
    }

    public function getUser(): ?BaseUser
    {
        return $this->user;
    }

    public function getDateCreated(): DateTimeInterface
    {
        return $this->dateCreated;
    }

    public function setCode(string $code): PasswordReminder
    {
        $this->code = $code;
        return $this;
    }

    public function setUser(BaseUser $user): PasswordReminder
    {
        $this->user = $user;
        return $this;
    }

    public function setDateCreated(DateTimeInterface $dateCreated): PasswordReminder
    {
        $this->dateCreated = $dateCreated;
        return $this;
    }
}
