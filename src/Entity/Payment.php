<?php

namespace App\Entity;

use App\Entity\Trait\TimestampableTrait;
use App\Repository\PaymentRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Represents the payment associated with a booking.
 */
#[ORM\Entity(repositoryClass: PaymentRepository::class)]
#[ORM\Table(name: 'payments')]
class Payment
{
    use TimestampableTrait;

    public const STATUS_PENDING = 'PENDING';
    public const STATUS_PAID = 'PAID';
    public const STATUS_REFUND_PENDING = 'REFUND_PENDING';
    public const STATUS_REFUNDED = 'REFUNDED';
    public const STATUS_REFUND_FAILED = 'REFUND_FAILED';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_PAID,
        self::STATUS_REFUND_PENDING,
        self::STATUS_REFUNDED,
        self::STATUS_REFUND_FAILED,
    ];

    public const STATUS_LABELS = [
        self::STATUS_PENDING => 'En attente',
        self::STATUS_PAID => 'paiement reçu',
        self::STATUS_REFUND_PENDING => 'Remboursement en cours',
        self::STATUS_REFUNDED => 'Remboursement effectué',
        self::STATUS_REFUND_FAILED => 'Échec du remboursement',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Booking associated with this payment.
     */
    #[ORM\OneToOne(inversedBy: 'payment')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Booking $booking = null;

    /**
     * Amount paid in cents.
     */
    #[ORM\Column]
    #[Assert\Positive(
        message: 'Le montant du paiement doit être supérieur à zéro.'
    )]
    private ?int $amount = null;

    /**
     * Current payment status.
     */
    #[ORM\Column(length: 30)]
    #[Assert\Choice(
        choices: self::STATUSES,
        message: 'Le statut de paiement est invalide.'
    )]
    private string $paymentStatus = self::STATUS_PENDING;

    /**
     * Stripe Checkout Session identifier.
     */
    #[ORM\Column(length: 255, unique: true, nullable: true)]
    private ?string $stripeSessionId = null;

    /**
     * Stripe Payment Intent identifier.
     */
    #[ORM\Column(length: 255, unique: true, nullable: true)]
    private ?string $stripePaymentIntentId = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBooking(): ?Booking
    {
        return $this->booking;
    }

    public function setBooking(Booking $booking): static
    {
        $this->booking = $booking;

        if ($booking->getPayment() !== $this) {
            $booking->setPayment($this);
        }

        return $this;
    }

    public function getAmount(): ?int
    {
        return $this->amount;
    }

    public function setAmount(int $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    public function getPaymentStatus(): string
    {
        return $this->paymentStatus;
    }

    public function getPaymentStatusLabel(): string
    {
        return self::STATUS_LABELS[$this->paymentStatus] ?? $this->paymentStatus;
    }

    public function setPaymentStatus(string $paymentStatus): static
    {
        $this->paymentStatus = $paymentStatus;

        return $this;
    }

    public function getStripeSessionId(): ?string
    {
        return $this->stripeSessionId;
    }

    public function setStripeSessionId(?string $stripeSessionId): static
    {
        $this->stripeSessionId = $stripeSessionId;

        return $this;
    }

    public function getStripePaymentIntentId(): ?string
    {
        return $this->stripePaymentIntentId;
    }

    public function setStripePaymentIntentId(?string $stripePaymentIntentId): static
    {
        $this->stripePaymentIntentId = $stripePaymentIntentId;

        return $this;
    }
}