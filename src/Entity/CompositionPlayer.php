<?php

namespace App\Entity;

use App\Repository\CompositionPlayerRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CompositionPlayerRepository::class)]
#[ORM\Table(name: 'composition_player', uniqueConstraints: [
    new ORM\UniqueConstraint(name: 'uniq_comp_player', columns: ['composition_id', 'player_id']),
    new ORM\UniqueConstraint(name: 'uniq_comp_jersey', columns: ['composition_id', 'jersey_number']),
])]
class CompositionPlayer
{
    public const STATUS_TITULAIRE = 'titulaire';
    public const STATUS_REMPLACANT = 'remplacant';
    public const STATUS_ABSENT = 'absent';

    public const STATUSES = [
        self::STATUS_TITULAIRE,
        self::STATUS_REMPLACANT,
        self::STATUS_ABSENT,
    ];

    public const POSITIONS = [
        'GK', 'RB', 'RCB', 'CB', 'LCB', 'LB',
        'RWB', 'LWB', 'DM', 'RCM', 'CM', 'LCM',
        'RM', 'LM', 'CAM', 'RW', 'LW', 'ST',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'compositionPlayers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Composition $composition = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Player $player = null;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: self::STATUSES, message: 'Choisis un statut valide.')]
    private string $status = '';

    #[ORM\Column(length: 20, nullable: true)]
    #[Assert\Choice(choices: self::POSITIONS, message: 'Choisis un poste valide.')]
    private ?string $positionCode = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Range(min: 1, max: 99, notInRangeMessage: 'Le numero doit etre entre 1 et 99.')]
    private ?int $jerseyNumber = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Range(min: 0, max: 100, notInRangeMessage: 'La coordonnee X doit etre entre 0 et 100.')]
    private ?int $coordX = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Range(min: 0, max: 100, notInRangeMessage: 'La coordonnee Y doit etre entre 0 et 100.')]
    private ?int $coordY = null;

    #[ORM\Column]
    private bool $isPlaced = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getComposition(): ?Composition
    {
        return $this->composition;
    }

    public function setComposition(?Composition $composition): static
    {
        $this->composition = $composition;

        return $this;
    }

    public function getPlayer(): ?Player
    {
        return $this->player;
    }

    public function setPlayer(?Player $player): static
    {
        $this->player = $player;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getPositionCode(): ?string
    {
        return $this->positionCode;
    }

    public function setPositionCode(?string $positionCode): static
    {
        $this->positionCode = $positionCode;

        return $this;
    }

    public function getJerseyNumber(): ?int
    {
        return $this->jerseyNumber;
    }

    public function setJerseyNumber(?int $jerseyNumber): static
    {
        $this->jerseyNumber = $jerseyNumber;

        return $this;
    }

    public function getCoordX(): ?int
    {
        return $this->coordX;
    }

    public function setCoordX(?int $coordX): static
    {
        $this->coordX = $coordX;

        return $this;
    }

    public function getCoordY(): ?int
    {
        return $this->coordY;
    }

    public function setCoordY(?int $coordY): static
    {
        $this->coordY = $coordY;

        return $this;
    }

    public function isPlaced(): bool
    {
        return $this->isPlaced;
    }

    public function setIsPlaced(bool $isPlaced): static
    {
        $this->isPlaced = $isPlaced;

        return $this;
    }
}
