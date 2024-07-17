<?php

declare(strict_types=1);

/**
 * LoginLog
 */

namespace FwsDoctrineAuth\Entity;

use DateTime;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(readOnly: false)]
#[ORM\Table(
    name: "login_log",
    options: [
        "collate" => "latin1_swedish_ci",
        "charset" => "latin1",
        "engine" => "InnoDB",
    ]
)]
class LoginLog implements EntityInterface
{
    #[ORM\Id,
        ORM\Column(
            name: "log_id",
            type: Types::INTEGER,
            nullable: false,
            options: ["unsigned" => true]
        ),
        ORM\GeneratedValue(strategy: "IDENTITY")
    ]
    private int|null $logId = null;

    #[ORM\ManyToOne(
        targetEntity: BaseUser::class,
        cascade: ["persist"],
        inversedBy: "logins"
    )]
    #[ORM\JoinColumn(
        name: "user_id",
        referencedColumnName: "user_id",
        nullable: false,
        onDelete: "CASCADE"
    )]
    private AuthUserInterface|null $user;

    #[ORM\Column(
        name: "used_2fa",
        type: Types::BOOLEAN,
        nullable: false,
        options: ["default" => false]
    )]
    private bool $used2fa = false;

    #[ORM\Column(
        name: "date_logged",
        type: Types::DATETIME_MUTABLE,
        nullable: false,
    )]
    private DateTimeInterface|null $dateLogged;

    public function __construct()
    {
        $this->dateLogged = new DateTime();
    }

    public function getLogId(): int|null
    {
        return $this->logId;
    }

    public function getUser(): AuthUserInterface|null
    {
        return $this->user;
    }

    public function getUsed2fa(): bool
    {
        return $this->used2fa;
    }

    public function getDateLogged(): DateTimeInterface|null
    {
        return $this->dateLogged;
    }

    /**
     * @return $this
     */
    public function setUser(AuthUserInterface $user): LoginLog
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return $this
     */
    public function setUsed2fa(bool $used2fa): LoginLog
    {
        $this->used2fa = $used2fa;
        return $this;
    }

    /**
     * @return $this
     */
    public function setDateLogged(DateTimeInterface $dateLogged): LoginLog
    {
        $this->dateLogged = $dateLogged;
        return $this;
    }
}
