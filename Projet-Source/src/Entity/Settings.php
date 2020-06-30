<?php

namespace App\Entity;

use App\Repository\SettingsRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=SettingsRepository::class)
 */
class Settings
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="boolean")
     */
    private $ModeMaintenance;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getModeMaintenance(): ?bool
    {
        return $this->ModeMaintenance;
    }

    public function setModeMaintenance(bool $ModeMaintenance): self
    {
        $this->ModeMaintenance = $ModeMaintenance;

        return $this;
    }
}
