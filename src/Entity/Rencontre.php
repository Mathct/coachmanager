<?php

namespace App\Entity;

use App\Repository\RencontreRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: RencontreRepository::class)]
class Rencontre
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom de l adversaire est obligatoire.')]
    #[Assert\Length(max: 255)]
    private ?string $opponent = null;

    #[ORM\Column]
    #[Assert\NotNull(message: 'La date du match est obligatoire.')]
    private ?\DateTimeImmutable $date = null;

    #[ORM\Column(nullable: true)]
    #[Assert\PositiveOrZero(message: 'Le score ne peut pas etre negatif.')]
    private ?int $score_team = null;

    #[ORM\Column(nullable: true)]
    #[Assert\PositiveOrZero(message: 'Le score ne peut pas etre negatif.')]
    private ?int $score_opponent = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $analyse = null;

    #[ORM\ManyToOne(inversedBy: 'rencontres')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Team $team = null;

    #[ORM\OneToOne(mappedBy: 'rencontre', cascade: ['persist', 'remove'])]
    private ?Composition $composition = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOpponent(): ?string
    {
        return $this->opponent;
    }

    public function setOpponent(string $opponent): static
    {
        $this->opponent = $opponent;

        return $this;
    }

    public function getDate(): ?\DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(\DateTimeImmutable $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getScoreTeam(): ?int
    {
        return $this->score_team;
    }

    public function setScoreTeam(?int $score_team): static
    {
        $this->score_team = $score_team;

        return $this;
    }

    public function getScoreOpponent(): ?int
    {
        return $this->score_opponent;
    }

    public function setScoreOpponent(?int $score_opponent): static
    {
        $this->score_opponent = $score_opponent;

        return $this;
    }

    public function getAnalyse(): ?string
    {
        return $this->analyse;
    }

    public function setAnalyse(?string $analyse): static
    {
        $this->analyse = $analyse;

        return $this;
    }

    public function getTeam(): ?Team
    {
        return $this->team;
    }

    public function setTeam(?Team $team): static
    {
        $this->team = $team;

        return $this;
    }

    public function getComposition(): ?Composition
    {
        return $this->composition;
    }

    public function setComposition(?Composition $composition): static
    {
        if ($composition === null && $this->composition !== null) {
            $this->composition->setRencontre(null);
        }

        if ($composition !== null && $composition->getRencontre() !== $this) {
            $composition->setRencontre($this);
        }

        $this->composition = $composition;

        return $this;
    }
}
