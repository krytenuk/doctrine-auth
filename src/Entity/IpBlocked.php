<?php

namespace FwsDoctrineAuth\Entity;

use Doctrine\ORM\Mapping as ORM;
use DateTimeImmutable;
use DateTimeInterface;

/**
 * UserBlocked
 * @ORM\Entity(repositoryClass="FwsDoctrineAuth\Entity\Repository\IpBlockedRepository")
 * @ORM\Table(name="ip_blocked", options={"collate"="latin1_swedish_ci", "charset"="latin1", "engine"="InnoDB"})
 * @author Garry Childs <info@freedomwebservices.net>
 */
class IpBlocked implements EntityInterface
{

    /**
     * @var int|null
     * @ORM\Column(name="block_id", type="integer", options={"unsigned"=true}, nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private ?int $blockId = null;

    /**
     * @var string|null
     *
     * @ORM\Column(name="ip_address", type="string", length=16, nullable=false)
     */
    private ?string $ipAddress = null;

    /**
     * @var string|null
     *
     * @ORM\Column(name="email_address", type="string", length=100, nullable=false)
     */
    private ?string $emailAddress = null;

    /**
     * @var DateTimeInterface|null
     *
     * @ORM\Column(name="date_blocked", type="datetime", nullable=false)
     */
    private ?DateTimeInterface $dateBlocked = null;
    
    public function __construct()
    {
        $this->dateBlocked = new DateTimeImmutable('now');
    }

    /**
     *
     * @return int|null
     */
    public function getBlockId(): ?int
    {
        return $this->blockId;
    }

    /**
     *
     * @return string|null
     */
    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    /**
     * 
     * @param string $ipAddress
     * @return IpBlocked
     */
    public function setIpAddress(string $ipAddress): IpBlocked
    {
        $this->ipAddress = $ipAddress;
        return $this;
    }

    /**
     *
     * @return string|null
     */
    public function getEmailAddress(): ?string
    {
        return $this->emailAddress;
    }
    
    /**
     * 
     * @param string $emailAddress
     * @return IpBlocked
     */
    public function setEmailAddress(string $emailAddress): IpBlocked
    {
        $this->emailAddress = $emailAddress;
        return $this;
    }

    /**
     *
     * @return DateTimeInterface|null
     */
    public function getDateBlocked(): ?DateTimeInterface
    {
        return $this->dateBlocked;
    }

    /**
     * 
     * @param DateTimeInterface $dateBlocked
     * @return IpBlocked
     */
    public function setDateBlocked(DateTimeInterface $dateBlocked): IpBlocked
    {
        $this->dateBlocked = $dateBlocked;
        return $this;
    }

}
