<?php

declare(strict_types=1);

/**
 * UserBlocked Entity
 */

namespace FwsDoctrineAuth\Entity;

use DateTime;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use FwsDoctrineAuth\Entity\Repository\IpBlockedRepository;

#[ORM\Entity(repositoryClass: IpBlockedRepository::class, readOnly: false)]
#[ORM\Table(
    name: "ip_blocked",
    options: [
        "collate" => "latin1_swedish_ci",
        "charset" => "latin1",
        "engine" => "InnoDB",
    ]
)]
class IpBlocked implements EntityInterface
{
    #[ORM\Id,
        ORM\Column(
            name: "block_id",
            type: Types::INTEGER,
            nullable: false,
            options: ["unsigned" => true]
        ),
        ORM\GeneratedValue(strategy: "IDENTITY")
    ]
    private int|null $blockId = null;

    #[ORM\Column(
        name: "ip_address",
        type: Types::STRING,
        length: 16,
        nullable: false
    )]
    private string|null $ipAddress;

    #[ORM\Column(
        name: "email_address",
        type: Types::STRING,
        length: 100,
        nullable: false
    )]
    private string|null $emailAddress;

    #[ORM\Column(
        name: "date_blocked",
        type: Types::DATETIME_MUTABLE,
        nullable: false,
    )]
    private DateTimeInterface|null $dateBlocked;

    public function __construct()
    {
        $this->dateBlocked = new DateTime('now');
    }

    /**
     * Get block id
     */
    public function getBlockId(): int|null
    {
        return $this->blockId;
    }

    /**
     * Get blocked IP address
     */
    public function getIpAddress(): string|null
    {
        return $this->ipAddress;
    }

    /**
     * Set blocked IP address
     */
    public function setIpAddress(string $ipAddress): IpBlocked
    {
        $this->ipAddress = $ipAddress;
        return $this;
    }

    /**
     * Get email address
     */
    public function getEmailAddress(): string|null
    {
        return $this->emailAddress;
    }

    /**
     * Set email address
     */
    public function setEmailAddress(string $emailAddress): IpBlocked
    {
        $this->emailAddress = $emailAddress;
        return $this;
    }

    /**
     * Get date & time blocked
     */
    public function getDateBlocked(): ?DateTimeInterface
    {
        return $this->dateBlocked;
    }

    /**
     * Set date & time blocked
     */
    public function setDateBlocked(DateTimeInterface $dateBlocked): IpBlocked
    {
        $this->dateBlocked = $dateBlocked;
        return $this;
    }
}
