<?php

namespace App\Entity;

use App\Entity\Trait\TimestampableTrait;
use App\Repository\RentalRateRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Represents a rental rate for a boat model
 */
#[ORM\Entity(repositoryClass: RentalRateRepository::class)]
#[ORM\Table(
    name: 'rental_rates',
    // Prevents duplicate formulas for the same boat model and start time.
    uniqueConstraints: [
        new ORM\UniqueConstraint(
            name: 'unique_boat_model_duration_start_time',
            columns: ['boat_model_id', 'duration_hours', 'start_time']
        )
    ]
)]
#[UniqueEntity(
    fields: ['boatModel', 'durationHours', 'startTime'],
    message: 'Une formule existe déjà pour ce modèle, cette durée et cette heure de départ.'
)]
class RentalRate
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(
        message: 'Le libellé de la formule est obligatoire.'
    )]
    #[Assert\Length(
        max: 100,
        maxMessage: 'Le libellé ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $label = null;

    #[ORM\Column]
    #[Assert\Positive(
        message: 'La durée doit être supérieure à zéro.'
    )]
    private ?int $durationHours = null;

    #[ORM\Column(type: 'time_immutable')]
    private ?\DateTimeImmutable $startTime = null;

    #[ORM\Column]
    #[Assert\Positive(
        message: 'Le prix doit être supérieur à zéro.'
    )]
    private ?int $price = null;

    /**
     * Indicates if the rental rate is available for new bookings.
     */
    #[ORM\Column(options: ['default' => true])]
    private bool $isActive = true;

    /**
     * Boat model associated with this rental rate.
     */
    #[ORM\ManyToOne(inversedBy: 'rentalRates')]
    #[ORM\JoinColumn(nullable: false)]
    private ?BoatModel $boatModel = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getDurationHours(): ?int
    {
        return $this->durationHours;
    }

    public function setDurationHours(int $durationHours): static
    {
        $this->durationHours = $durationHours;

        return $this;
    }

    public function getStartTime(): ?\DateTimeImmutable
    {
        return $this->startTime;
    }

    public function setStartTime(\DateTimeImmutable $startTime): static
    {
        $this->startTime = $startTime;

        return $this;
    }

    public function getEndTime(): ?\DateTimeImmutable
    {
        if ($this->startTime === null || $this->durationHours === null) {
            return null;
        }

        return $this->startTime->modify(
            '+' . $this->durationHours . ' hours'
        );
    }

    public function getPrice(): ?int
    {
        return $this->price;
    }

    public function setPrice(int $price): static
    {
        $this->price = $price;

        return $this;
    }

    /**
     * Checks if the rental rate is active.
     */
    public function isActive(): bool
    {
        return $this->isActive;
    }

    /**
     * Sets the active status of the rental rate.
     */
    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getBoatModel(): ?BoatModel
    {
        return $this->boatModel;
    }

    public function setBoatModel(BoatModel $boatModel): static
    {
        $this->boatModel = $boatModel;

        return $this;
    }
}