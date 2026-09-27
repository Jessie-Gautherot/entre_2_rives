<?php

namespace App\Entity;

use App\Entity\Trait\TimestampableTrait;
use App\Repository\BoatRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BoatRepository::class)]
#[ORM\Table(name: 'boats')]
#[UniqueEntity(
    fields: ['name'],
    message: 'Ce bateau existe déjà.'
)]
class Boat
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100, unique: true)]
    #[Assert\NotBlank(
        message: 'Le nom du bateau est obligatoire.'
    )]
    #[Assert\Length(
        max: 100,
        maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $name = null;

    #[ORM\Column(options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\ManyToOne(inversedBy: 'boats')]
    #[ORM\JoinColumn(nullable: false)]
    private ?BoatModel $boatModel = null;

    /**
     * Bookings assigned to this physical boat.
     *
     * @var Collection<int, Booking>
     */
    #[ORM\OneToMany(
        mappedBy: 'boat',
        targetEntity: Booking::class
    )]
    private Collection $bookings;

    public function __construct()
    {
        $this->bookings = new ArrayCollection();
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

    public function isActive(): bool
    {
        return $this->isActive;
    }

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

    /**
     * @return Collection<int, Booking>
     */
    public function getBookings(): Collection
    {
        return $this->bookings;
    }

    /**
     * Adds a booking to this physical boat.
     */
    public function addBooking(Booking $booking): static
    {
        if (!$this->bookings->contains($booking)) {
            $this->bookings->add($booking);
            $booking->setBoat($this);
        }

        return $this;
    }
}