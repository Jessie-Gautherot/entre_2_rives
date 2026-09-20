<?php

namespace App\Service;

use App\Entity\Booking;
use App\Entity\Payment;
use Doctrine\ORM\EntityManagerInterface;

class BookingCancellationService
{
    public function __construct(
        private readonly StripeService $stripeService,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Checks whether a booking can be cancelled.
     */
    private function checkCancellation(
        Booking $booking,
        bool $isAdmin = false,
    ): void {
        if ($booking->getStatus() !== Booking::STATUS_CONFIRMED) {
            throw new \LogicException(
                'Seule une réservation confirmée peut être annulée.'
            );
        }

        if ($booking->getPayment() === null) {
            throw new \LogicException(
                'Aucun paiement n’est associé à cette réservation.'
            );
        }

        $now = new \DateTimeImmutable();
        $cancellationDeadline = $booking->getStartAt()->modify('-48 hours');

        // Allows the admin to bypass the 48-hour cancellation limit.
        // Admin can cancel at any time in case of an internal issue.
        if (!$isAdmin && $now > $cancellationDeadline) {
            throw new \LogicException(
                'La réservation ne peut plus être annulée moins de 48 heures avant le départ.'
            );
        }
    }

    /**
     * Cancels a booking and requests its refund.
     */
    public function cancel(
        Booking $booking,
        bool $isAdmin = false,
    ): void {
        $this->checkCancellation($booking, $isAdmin);

        $payment = $booking->getPayment();

        $booking->setStatus(Booking::STATUS_CANCELLATION_PENDING);
        $payment->setPaymentStatus(Payment::STATUS_REFUND_PENDING);

        $this->entityManager->flush();

        $this->stripeService->createRefund($payment);
    }
}