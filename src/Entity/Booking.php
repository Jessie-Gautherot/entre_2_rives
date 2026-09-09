<?php

namespace App\Entity;

use App\Entity\Trait\TimestampableTrait;
use App\Repository\BookingRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Represents a booking made by a user.
 */
#[ORM\Entity(repositoryClass: BookingRepository::class)]
#[ORM\Table(name: 'bookings')]
class Booking
{
    use TimestampableTrait;

    public const STATUS_PENDING = 'PENDING';
    public const STATUS_CONFIRMED = 'CONFIRMED';
    public const STATUS_CANCELLATION_PENDING = 'CANCELLATION_PENDING';
    public const STATUS_CANCELLED = 'CANCELLED';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_CONFIRMED,
        self::STATUS_CANCELLATION_PENDING,
        self::STATUS_CANCELLED,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * User who made the booking.
     */
    #[ORM\ManyToOne(inversedBy: 'bookings')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    /**
     * Boat model selected by the user.
     */
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?BoatModel $boatModel = null;

    /**
     * Physical boat assigned to the booking.
     */
    #[ORM\ManyToOne(inversedBy: 'bookings')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Boat $boat = null;

    /**
     * Rental rate selected for this booking.
     */
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?RentalRate $rentalRate = null;

    /**
     * Payment associated with the booking.
     */
    #[ORM\OneToOne(mappedBy: 'booking')]
    private ?Payment $payment = null;

    /**
     * Number of passengers for this booking.
     */
    #[ORM\Column]
    #[Assert\Positive(
        message: 'Le nombre de passagers doit être supérieur à zéro.'
    )]
    private ?int $passengerCount = null;

    /**
     * Rental rate label saved at booking time for history.
     */
    #[ORM\Column(length: 100)]
    private ?string $rentalRateLabel = null;

    /**
     * Total price saved in cents at booking time.
     */
    #[ORM\Column]
    private ?int $totalPrice = null;

    /**
     * Start date and time saved at booking time.
     */
    #[ORM\Column]
    private ?\DateTimeImmutable $startAt = null;

    /**
     * End date and time saved at booking time.
     */
    #[ORM\Column]
    private ?\DateTimeImmutable $endAt = null;

    /**
     * Current booking status.
     */
    #[ORM\Column(length: 30)]
    #[Assert\Choice(
        choices: self::STATUSES,
        message: 'Le statut de réservation est invalide.'
    )]
    private string $status = self::STATUS_PENDING;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;

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

    public function getBoat(): ?Boat
    {
        return $this->boat;
    }

    public function setBoat(Boat $boat): static
    {
        $this->boat = $boat;

        return $this;
    }

    public function getRentalRateLabel(): ?string
    {
        return $this->rentalRateLabel;
    }

    public function setRentalRateLabel(string $rentalRateLabel): static
    {
        $this->rentalRateLabel = $rentalRateLabel;

        return $this;
    }

    public function getRentalRate(): ?RentalRate
    {
        return $this->rentalRate;
    }

    public function setRentalRate(RentalRate $rentalRate): static
    {
        $this->rentalRate = $rentalRate;

        return $this;
    }

    public function getPayment(): ?Payment
    {
        return $this->payment;
    }

    public function setPayment(?Payment $payment): static
    {
        $this->payment = $payment;

        if ($payment !== null && $payment->getBooking() !== $this) {
            $payment->setBooking($this);
        }

        return $this;
    }

    public function getPassengerCount(): ?int
    {
        return $this->passengerCount;
    }

    public function setPassengerCount(int $passengerCount): static
    {
        $this->passengerCount = $passengerCount;

        return $this;
    }

    public function getTotalPrice(): ?int
    {
        return $this->totalPrice;
    }

    public function setTotalPrice(int $totalPrice): static
    {
        $this->totalPrice = $totalPrice;

        return $this;
    }

    public function getStartAt(): ?\DateTimeImmutable
    {
        return $this->startAt;
    }

    public function setStartAt(\DateTimeImmutable $startAt): static
    {
        $this->startAt = $startAt;

        return $this;
    }

    public function getEndAt(): ?\DateTimeImmutable
    {
        return $this->endAt;
    }

    public function setEndAt(\DateTimeImmutable $endAt): static
    {
        $this->endAt = $endAt;

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
}