<?php

namespace App\Entity;

use App\Repository\CompositionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CompositionRepository::class)]
class Composition
{
    public const FORMATIONS = [
        '4-3-3',
        '4-2-3-1',
        '4-4-2',
        '3-5-2',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'composition')]
    #[ORM\JoinColumn(nullable: false, unique: true)]
    private ?Rencontre $rencontre = null;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: self::FORMATIONS, message: 'Choisis une formation valide.')]
    private string $formation = '4-3-3';

    #[ORM\Column]
    private bool $isValidated = false;

    #[ORM\OneToMany(targetEntity: CompositionPlayer::class, mappedBy: 'composition', orphanRemoval: true, cascade: ['persist'])]
    private Collection $compositionPlayers;

    public function __construct()
    {
        $this->compositionPlayers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRencontre(): ?Rencontre
    {
        return $this->rencontre;
    }

    public function setRencontre(?Rencontre $rencontre): static
    {
        $this->rencontre = $rencontre;

        return $this;
    }

    public function getFormation(): string
    {
        return $this->formation;
    }

    public function setFormation(string $formation): static
    {
        $this->formation = $formation;

        return $this;
    }

    public function isValidated(): bool
    {
        return $this->isValidated;
    }

    public function setIsValidated(bool $isValidated): static
    {
        $this->isValidated = $isValidated;

        return $this;
    }

    public function getCompositionPlayers(): Collection
    {
        return $this->compositionPlayers;
    }

    public function addCompositionPlayer(CompositionPlayer $compositionPlayer): static
    {
        if (!$this->compositionPlayers->contains($compositionPlayer)) {
            $this->compositionPlayers->add($compositionPlayer);
            $compositionPlayer->setComposition($this);
        }

        return $this;
    }

    public function removeCompositionPlayer(CompositionPlayer $compositionPlayer): static
    {
        if ($this->compositionPlayers->removeElement($compositionPlayer)) {
            if ($compositionPlayer->getComposition() === $this) {
                $compositionPlayer->setComposition(null);
            }
        }

        return $this;
    }
}
