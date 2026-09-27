<?php

namespace App\Tests\Payment;

use App\Entity\Booking;
use App\Entity\Payment;
use App\Repository\BookingRepository;
use App\Service\StripeService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Stripe\Checkout\Session;
use Stripe\Refund;
use Stripe\StripeClient;

class StripeServiceTest extends TestCase
{
    /**
     * Checks that a completed Stripe checkout marks
     * the payment as paid and confirms the booking.
     */
    public function testCompletedCheckout(): void
    {
        $booking = new Booking();
        $payment = new Payment();

        $payment->setStripeSessionId('cs_test_123');
        $booking->setPayment($payment);

        // Mocks the repository to return the booking referenced by Stripe.
        $bookingRepository = $this->createMock(BookingRepository::class);

        $bookingRepository
            ->expects($this->once())
            ->method('find')
            ->with('42')
            ->willReturn($booking);

        // Mocks the EntityManager to verify that the changes are saved.
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $entityManager
            ->expects($this->once())
            ->method('flush');

        // Uses the real service with a mocked Stripe client to avoid external API calls.
        $stripeService = new StripeService(
            $this->createMock(StripeClient::class),
            'http://localhost:8000',
            $entityManager,
            $bookingRepository,
        );

        // Builds the Stripe checkout session received after a successful payment.
        $session = Session::constructFrom([
            'id' => 'cs_test_123',
            'client_reference_id' => '42',
            'payment_intent' => 'pi_test_123',
        ]);

        $stripeService->handleCompletedCheckout($session);

        $this->assertSame(
            Payment::STATUS_PAID,
            $payment->getPaymentStatus()
        );

        $this->assertSame(
            Booking::STATUS_CONFIRMED,
            $booking->getStatus()
        );

        $this->assertSame(
            'pi_test_123',
            $payment->getStripePaymentIntentId()
        );
    }

    /**
     * Checks that a failed Stripe refund
     * marks the payment as failed while keeping the booking pending cancellation.
     */
    public function testFailedRefund(): void
    {
        $booking = new Booking();
        $booking->setStatus(Booking::STATUS_CANCELLATION_PENDING);

        $payment = new Payment();
        $payment->setPaymentStatus(Payment::STATUS_REFUND_PENDING);

        $booking->setPayment($payment);

        // Mocks the repository to return the booking referenced in the refund metadata.
        $bookingRepository = $this->createMock(BookingRepository::class);

        $bookingRepository
            ->expects($this->once())
            ->method('find')
            ->with('42')
            ->willReturn($booking);

        // Mocks the EntityManager to verify that the failed refund state is saved.
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $entityManager
            ->expects($this->once())
            ->method('flush');

        // Uses the real service with a mocked Stripe client to avoid external API calls.
        $stripeService = new StripeService(
            $this->createMock(StripeClient::class),
            'http://localhost:8000',
            $entityManager,
            $bookingRepository,
        );

        // Builds the Stripe refund received after a failed refund attempt.
        $refund = Refund::constructFrom([
            'id' => 're_test_failed',
            'metadata' => [
                'booking_id' => '42',
            ],
        ]);

        $stripeService->handleFailedRefund($refund);

        $this->assertSame(
            Payment::STATUS_REFUND_FAILED,
            $payment->getPaymentStatus()
        );

        $this->assertSame(
            Booking::STATUS_CANCELLATION_PENDING,
            $booking->getStatus()
        );
    }
}