<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

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
     * @ORM\Column(name="block_id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private ?int $blockId = null;

    /**
     * @var string
     *
     * @ORM\Column(name="ip_address", type="string", length=16, nullable=false)
     */
    private string $ipAddress;

    /**
     * @var string
     *
     * @ORM\Column(name="email_address", type="string", length=100, nullable=false)
     */
    private string $emailAddress;

    /**
     * @var DateTimeInterface
     *
     * @ORM\Column(name="date_blocked", type="datetime", nullable=false)
     */
    private DateTimeInterface $dateBlocked;
    
    public function __construct()
    {
        $this->dateBlocked = new DateTimeImmutable('now');
    }
    
    /**
     * 
     * @return int
     */
    public function getBlockId(): int
    {
        return $this->blockId;
    }
    
    /**
     * 
     * @return string
     */
    public function getIpAddress(): string
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
     * @return string
     */
    public function getEmailAddress(): string
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
     * @return DateTimeInterface
     */
    public function getDateBlocked(): DateTimeInterface
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
