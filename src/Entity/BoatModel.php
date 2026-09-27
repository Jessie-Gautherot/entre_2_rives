<?php

namespace App\Entity;

use App\Entity\Trait\TimestampableTrait;
use App\Repository\BoatModelRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BoatModelRepository::class)]
#[ORM\Table(name: 'boat_models')]
#[UniqueEntity(
    fields: ['name'],
    message: 'Ce modèle de bateau existe déjà.'
)]
class BoatModel
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100, unique: true)]
    #[Assert\NotBlank(
        message: 'Le nom du modèle est obligatoire.'
    )]
    #[Assert\Length(
        max: 100,
        maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $name = null;

    #[ORM\Column(length: 120, unique: true)]
    private ?string $slug = null;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(
        message: 'La description est obligatoire.'
    )]
    private ?string $description = null;

    #[ORM\Column]
    #[Assert\Positive(
        message: 'La capacité doit être supérieure à zéro.'
    )]
    private ?int $capacity = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(
        message: 'L’image principale est obligatoire.'
    )]
    private ?string $mainImage = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $secondImage = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $thirdImage = null;

    /**
     * Physical boats belonging to this model.
     *
     * @var Collection<int, Boat>
     */
    #[ORM\OneToMany(
        mappedBy: 'boatModel',
        targetEntity: Boat::class
    )]
    private Collection $boats;

    /**
     * Rental rates available for this model.
     *
     * @var Collection<int, RentalRate>
     */
    #[ORM\OneToMany(
        mappedBy: 'boatModel',
        targetEntity: RentalRate::class
    )]
    private Collection $rentalRates;

    public function __construct()
    {
        $this->boats = new ArrayCollection();
        $this->rentalRates = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getCapacity(): ?int
    {
        return $this->capacity;
    }

    public function setCapacity(int $capacity): static
    {
        $this->capacity = $capacity;

        return $this;
    }

    public function getMainImage(): ?string
    {
        return $this->mainImage;
    }

    public function setMainImage(string $mainImage): static
    {
        $this->mainImage = $mainImage;

        return $this;
    }

    public function getSecondImage(): ?string
    {
        return $this->secondImage;
    }

    public function setSecondImage(?string $secondImage): static
    {
        $this->secondImage = $secondImage;

        return $this;
    }

    public function getThirdImage(): ?string
    {
        return $this->thirdImage;
    }

    public function setThirdImage(?string $thirdImage): static
    {
        $this->thirdImage = $thirdImage;

        return $this;
    }

    /**
     * @return Collection<int, Boat>
     */
    public function getBoats(): Collection
    {
        return $this->boats;
    }

    /**
     * Adds a physical boat to this model.
     */
    public function addBoat(Boat $boat): static
    {
        if (!$this->boats->contains($boat)) {
            $this->boats->add($boat);
            $boat->setBoatModel($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, RentalRate>
     */
    public function getRentalRates(): Collection
    {
        return $this->rentalRates;
    }

    /**
     * Adds a rental rate to this model.
     */
    public function addRentalRate(RentalRate $rentalRate): static
    {
        if (!$this->rentalRates->contains($rentalRate)) {
            $this->rentalRates->add($rentalRate);
            $rentalRate->setBoatModel($this);
        }

        return $this;
    }
}