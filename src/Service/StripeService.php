<?php

namespace App\Service;

use App\Entity\Booking;
use App\Entity\Payment;
use App\Repository\BookingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Checkout\Session;
use Stripe\Refund;
use Stripe\StripeClient;

/**
 * Handles interactions with Stripe.
 */
class StripeService
{
    public function __construct(
        private readonly StripeClient $stripe,
        private readonly string $appUrl,
        private readonly EntityManagerInterface $entityManager,
        private readonly BookingRepository $bookingRepository,
    ) {
    }

    /**
     * Creates a Stripe Checkout Session for a booking.
     *
     * Returns the Stripe Checkout URL used to redirect the customer.
     */
    public function createCheckoutSession(Booking $booking): string
    {
        $payment = $booking->getPayment();

        if ($payment === null) {
            throw new \LogicException(
                'Aucun paiement n’est associé à cette réservation.'
            );
        }

        // Creates the Stripe Checkout Session with the booking payment data.
        $session = $this->stripe->checkout->sessions->create([
            'mode' => 'payment',
            'client_reference_id' => (string) $booking->getId(),

            'line_items' => [
                [
                    'price_data' => [
                        'currency' => 'eur',
                        'unit_amount' => $payment->getAmount(),
                        'product_data' => [
                            'name' => 'Location de bateau - Entre 2 Rives',
                        ],
                    ],
                    'quantity' => 1,
                ],
            ],

            'success_url' => $this->appUrl . '/stripe/succes',
            'cancel_url' => $this->appUrl . '/stripe/annulation',
        ]);

        // Stores the Stripe Session ID for later webhook verification.
        $payment->setStripeSessionId($session->id);

        $this->entityManager->flush();

        if ($session->url === null) {
            throw new \LogicException(
                'Stripe n’a pas retourné d’URL de paiement.'
            );
        }

        return $session->url;
    }

    /**
     * Confirms a booking after a completed Stripe Checkout Session.
     */
    public function handleCompletedCheckout(Session $session): void
    {
        $bookingId = $session->client_reference_id;

        if ($bookingId === null) {
            return;
        }

        $booking = $this->bookingRepository->find($bookingId);

        if ($booking === null) {
            return;
        }

        $payment = $booking->getPayment();

        if ($payment === null) {
            return;
        }

        // Prevents processing the same successful payment more than once.
        if ($payment->getPaymentStatus() === Payment::STATUS_PAID) {
            return;
        }

        // Ensures the webhook belongs to the expected Checkout Session.
        if ($payment->getStripeSessionId() !== $session->id) {
            return;
        }

        $paymentIntentId = $session->payment_intent;

        if (!is_string($paymentIntentId)) {
            return;
        }

        // Confirms the payment and the related booking.
        $payment->setStripePaymentIntentId($paymentIntentId);
        $payment->setPaymentStatus(Payment::STATUS_PAID);
        $booking->setStatus(Booking::STATUS_CONFIRMED);

        $this->entityManager->flush();
    }

    /**
     * Creates a Stripe refund for a payment.
     */
    public function createRefund(Payment $payment): void
    {
        if ($payment->getPaymentStatus() !== Payment::STATUS_REFUND_PENDING) {
            throw new \LogicException(
                'Ce paiement n’est pas en attente de remboursement.'
            );
        }

        $paymentIntentId = $payment->getStripePaymentIntentId();

        if ($paymentIntentId === null) {
            throw new \LogicException(
                'Aucun identifiant de paiement Stripe n’est associé à ce paiement.'
            );
        }

        try {
            // Requests the refund using the original Stripe Payment Intent.
            $this->stripe->refunds->create([
                'payment_intent' => $paymentIntentId,
                'reason' => 'requested_by_customer',
                'metadata' => [
                    'booking_id' => (string) $payment->getBooking()->getId(),
                ],
            ]);
        } catch (\Throwable $exception) {
            // Keeps track of an immediate failure when contacting Stripe.
            $payment->setPaymentStatus(Payment::STATUS_REFUND_FAILED);

            $this->entityManager->flush();

            throw $exception;
        }
    }

    /**
     * Finalizes a booking cancellation after a successful Stripe refund.
     */
    public function handleCreatedRefund(Refund $refund): void
    {
        if ($refund->status !== 'succeeded') {
            return;
        }

        // Retrieves the booking ID previously stored in Stripe metadata.
        $bookingId = $refund->metadata->booking_id ?? null;

        if ($bookingId === null) {
            return;
        }

        $booking = $this->bookingRepository->find($bookingId);

        if ($booking === null) {
            return;
        }

        if ($booking->getStatus() !== Booking::STATUS_CANCELLATION_PENDING) {
            return;
        }

        $payment = $booking->getPayment();

        if ($payment === null) {
            return;
        }

        if ($payment->getPaymentStatus() !== Payment::STATUS_REFUND_PENDING) {
            return;
        }

        // Finalizes the refund and cancellation after Stripe confirmation.
        $payment->setPaymentStatus(Payment::STATUS_REFUNDED);
        $booking->setStatus(Booking::STATUS_CANCELLED);

        $this->entityManager->flush();
    }

    /**
     * Marks a refund as failed after a Stripe refund failure.
     */
    public function handleFailedRefund(Refund $refund): void
    {
        // Retrieves the booking ID previously stored in Stripe metadata.
        $bookingId = $refund->metadata->booking_id ?? null;

        if ($bookingId === null) {
            return;
        }

        $booking = $this->bookingRepository->find($bookingId);

        if ($booking === null) {
            return;
        }

        if ($booking->getStatus() !== Booking::STATUS_CANCELLATION_PENDING) {
            return;
        }

        $payment = $booking->getPayment();

        if ($payment === null) {
            return;
        }

        if ($payment->getPaymentStatus() !== Payment::STATUS_REFUND_PENDING) {
            return;
        }

        // Records the refund failure while keeping the cancellation pending.
        $payment->setPaymentStatus(Payment::STATUS_REFUND_FAILED);

        $this->entityManager->flush();
    }
}