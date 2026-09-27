<?php

namespace App\Tests\Booking;

use App\Entity\Booking;
use App\Entity\Payment;
use App\Entity\User;
use App\Repository\BoatModelRepository;
use App\Repository\BookingRepository;
use App\Repository\RentalRateRepository;
use App\Repository\UserRepository;
use App\Service\BookingService;
use App\Service\StripeService;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Refund;
use Stripe\StripeClient;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

class BookingCancellationFlowTest extends WebTestCase
{
    /**
     * Creates a temporary confirmed and paid booking for the cancellation flow.
     */
    private function createPaidBooking(User $alice): Booking
    {
        $container = self::getContainer();

        // Uses the real application services and fixture data to create the booking.
        $entityManager = $container->get(EntityManagerInterface::class);
        $boatModelRepository = $container->get(BoatModelRepository::class);
        $rentalRateRepository = $container->get(RentalRateRepository::class);
        $bookingService = $container->get(BookingService::class);

        $boatModel = $boatModelRepository->findOneBy([]);

        $this->assertNotNull($boatModel);

        $rentalRate = $rentalRateRepository->findOneBy([
            'boatModel' => $boatModel,
            'isActive' => true,
        ]);

        $this->assertNotNull($rentalRate);

        // Chooses a future opening day that remains eligible for cancellation.
        $date = (new \DateTimeImmutable('+2 weeks'))
            ->modify('next Wednesday');

        $booking = $bookingService->makeBooking(
            $alice,
            $boatModel,
            $rentalRate,
            $date,
            1,
        );

        $payment = $booking->getPayment();

        $this->assertNotNull($payment);

        // Simulates the state reached after a successful Stripe payment.
        $booking->setStatus(Booking::STATUS_CONFIRMED);
        $payment->setPaymentStatus(Payment::STATUS_PAID);
        $payment->setStripePaymentIntentId('pi_test_cancel_123');

        $entityManager->flush();

        return $booking;
    }

    /**
     * Removes the temporary booking and payment created for the test.
     */
    private function deleteBooking(int $bookingId, int $paymentId): void
    {
        $entityManager = self::getContainer()
            ->get(EntityManagerInterface::class);

        // Clears Doctrine before reloading the entities from the database.
        $entityManager->clear();

        $booking = $entityManager->find(Booking::class, $bookingId);
        $payment = $entityManager->find(Payment::class, $paymentId);

        if ($booking !== null) {
            $entityManager->remove($booking);
        }

        if ($payment !== null) {
            $entityManager->remove($payment);
        }

        $entityManager->flush();
    }

    /**
     * Checks the complete cancellation flow for a confirmed and paid booking.
     */
    public function testAliceCanCancelPaidBooking(): void
    {
        $client = static::createClient();
        $client->disableReboot();

        $container = self::getContainer();

        $entityManager = $container->get(EntityManagerInterface::class);
        $bookingRepository = $container->get(BookingRepository::class);
        $userRepository = $container->get(UserRepository::class);
        $router = $container->get(RouterInterface::class);

        // Uses Alice from the test fixtures as the authenticated customer.
        $alice = $userRepository->findOneBy([
            'email' => 'alice@test.fr',
        ]);

        $this->assertNotNull($alice);

        // Creates the paid booking that will be cancelled during the test.
        $booking = $this->createPaidBooking($alice);

        $bookingId = $booking->getId();

        $this->assertNotNull($bookingId);

        $paymentId = $booking->getPayment()?->getId();

        $this->assertNotNull($paymentId);

        try {
            // Authenticates Alice directly without going through the login form.
            $client->loginUser($alice);

            // Opens the account page to retrieve the real cancellation form and CSRF token.
            $crawler = $client->request(
                'GET',
                $router->generate('app_account')
            );

            $this->assertResponseIsSuccessful();

            // Mocks StripeService to verify the refund request without calling Stripe.
            $stripeServiceMock = $this->createMock(
                StripeService::class
            );

            $stripeServiceMock
                ->expects($this->once())
                ->method('createRefund')
                ->with($this->isInstanceOf(Payment::class));

            // Replaces the real StripeService only for the cancellation request.
            $container->set(
                StripeService::class,
                $stripeServiceMock,
            );

            $cancelUrl = $router->generate(
                'app_booking_cancel',
                ['id' => $bookingId],
            );

            $csrfToken = $crawler
                ->filter(
                    'form[action="' . $cancelUrl . '"] input[name="_token"]'
                )
                ->attr('value');

            $this->assertNotEmpty($csrfToken);

            // Sends the real cancellation request with the CSRF token from the form.
            $client->request(
                'POST',
                $cancelUrl,
                [
                    '_token' => $csrfToken,
                ],
            );

            $this->assertResponseRedirects(
                $router->generate('app_account')
            );

            // Reloads the booking to check the state saved after the cancellation request.
            $entityManager->clear();

            $booking = $bookingRepository->find($bookingId);

            $this->assertNotNull($booking);

            $payment = $booking->getPayment();

            $this->assertNotNull($payment);

            $this->assertSame(
                Booking::STATUS_CANCELLATION_PENDING,
                $booking->getStatus()
            );

            $this->assertSame(
                Payment::STATUS_REFUND_PENDING,
                $payment->getPaymentStatus()
            );

            // Builds the Stripe refund event received after a successful refund.
            $refund = Refund::constructFrom([
                'id' => 're_test_cancel_123',
                'status' => 'succeeded',
                'metadata' => [
                    'booking_id' => (string) $bookingId,
                ],
            ]);

            // Uses the real service with a mocked Stripe client to process the refund event.
            $stripeService = new StripeService(
                $this->createMock(StripeClient::class),
                'http://localhost:8000',
                $entityManager,
                $bookingRepository,
            );

            $stripeService->handleCreatedRefund($refund);

            // Reloads the entities after the Stripe refund confirmation.
            $entityManager->clear();

            $booking = $bookingRepository->find($bookingId);

            $this->assertNotNull($booking);

            $payment = $booking->getPayment();

            $this->assertNotNull($payment);

            $this->assertSame(
                Booking::STATUS_CANCELLED,
                $booking->getStatus()
            );

            $this->assertSame(
                Payment::STATUS_REFUNDED,
                $payment->getPaymentStatus()
            );
        } finally {
            // Always removes the temporary data, even if an assertion fails.
            $this->deleteBooking($bookingId, $paymentId);
        }
    }
}
