<?php

namespace App\Entity;

use App\Repository\VillesRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=VillesRepository::class)
 */
class Villes
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
    private $NomVille;

    /**
     * @ORM\ManyToOne(targetEntity=Pays::class, inversedBy="Villes")
     * @ORM\JoinColumn(nullable=false)
     */
    private $pays;


    public function __construct()
    {
        $this->Ville = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNomVille(): ?string
    {
        return $this->NomVille;
    }

    public function setNomVille(string $NomVille): self
    {
        $this->NomVille = $NomVille;

        return $this;
    }

    public function getPays(): ?Pays
    {
        return $this->pays;
    }

    public function setPays(?Pays $pays): self
    {
        $this->pays = $pays;

        return $this;
    }

    /**
     * @return Collection|Agences[]
     */
    public function getVille(): Collection
    {
        return $this->Ville;
    }

    public function addVille(Agences $ville): self
    {
        if (!$this->Ville->contains($ville)) {
            $this->Ville[] = $ville;
            $ville->setVille($this);
        }

        return $this;
    }

    public function removeVille(Agences $ville): self
    {
        if ($this->Ville->contains($ville)) {
            $this->Ville->removeElement($ville);
            // set the owning side to null (unless already changed)
            if ($ville->getVille() === $this) {
                $ville->setVille(null);
            }
        }

        return $this;
    }
}
