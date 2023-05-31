<?php

namespace App\Entity;

use App\Repository\SportPlanningRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;

#[ORM\Entity(repositoryClass: SportPlanningRepository::class)]
class SportPlanning
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private array $promotion = [];

    #[ORM\Column(length: 255)]
    private ?\DateTime $startingDateTime = null;


    #[ORM\Column(length: 255)]
    #[Assert\NotEqualTo(propertyPath: "startingDateTime")]
    private ?\DateTime $endingDateTime = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $place = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPromotion(): ?array
    {
        return $this->promotion;
    }

    public function setPromotion(array $promotion): self
    {
        $this->promotion = $promotion;

        return $this;
    }

    public function getStartingDateTime(): \DateTime
    {
        return $this->startingDateTime;
    }

    public function setStartingDateTime(\DateTime $startingDateTime): self
    {
        $this->startingDateTime = $startingDateTime;

        return $this;
    }

    public function getEndingDateTime(): \DateTime
    {
        return $this->endingDateTime;
    }

    public function setEndingDateTime(\DateTime $endingDateTime): self
    {
        $this->endingDateTime = $endingDateTime;

        return $this;
    }

    public function getPlace(): ?string
    {
        return $this->place;
    }

    public function setPlace(?string $place): self
    {
        $this->place = $place;

        return $this;
    }
}
