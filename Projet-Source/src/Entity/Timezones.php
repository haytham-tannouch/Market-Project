<?php

namespace App\Entity;

use App\Repository\TimezonesRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=TimezonesRepository::class)
 */
class Timezones
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $timezoneGroupeFr;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $timezoneGroupeEn;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $timezoneDetail;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTimezoneGroupeFr(): ?string
    {
        return $this->timezoneGroupeFr;
    }

    public function setTimezoneGroupeFr(string $timezoneGroupeFr): self
    {
        $this->timezoneGroupeFr = $timezoneGroupeFr;

        return $this;
    }

    public function getTimezoneGroupeEn(): ?string
    {
        return $this->timezoneGroupeEn;
    }

    public function setTimezoneGroupeEn(string $timezoneGroupeEn): self
    {
        $this->timezoneGroupeEn = $timezoneGroupeEn;

        return $this;
    }

    public function getTimezoneDetail(): ?string
    {
        return $this->timezoneDetail;
    }

    public function setTimezoneDetail(string $timezoneDetail): self
    {
        $this->timezoneDetail = $timezoneDetail;

        return $this;
    }
}
