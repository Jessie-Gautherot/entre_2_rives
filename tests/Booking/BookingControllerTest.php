<?php

namespace App\Tests\Booking;

use App\Entity\Booking;
use App\Entity\Payment;
use App\Repository\BoatModelRepository;
use App\Repository\RentalRateRepository;
use App\Repository\UserRepository;
use App\Service\StripeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Tests the main booking web flow.
 */
class BookingControllerTest extends WebTestCase
{
    /**
     * Checks that the booking page is accessible
     * to a logged-in user.
     */
    public function testBookingPage(): void
    {
        $client = static::createClient();

        $container = static::getContainer();

        $userRepository = $container->get(UserRepository::class);

        // Uses the active client from the test fixtures.
        $user = $userRepository->findOneBy([
            'email' => 'alice@test.fr',
        ]);

        $this->assertNotNull($user);

        $client->loginUser($user);

        $client->request('GET', '/reservation');

        $this->assertResponseIsSuccessful();

        $this->assertSelectorTextContains(
            'h1',
            'Réserver un bateau',
        );

        $this->assertSelectorExists('#booking-form');
    }

    /**
     * Checks that a valid booking form submission
     * creates a booking and redirects to Stripe Checkout.
     */
    public function testCreateBooking(): void
    {
        $client = static::createClient();

        // Keeps the same kernel between the GET and POST requests.
        $client->disableReboot();

        $container = static::getContainer();

        $userRepository = $container->get(UserRepository::class);
        $boatModelRepository = $container->get(BoatModelRepository::class);
        $rentalRateRepository = $container->get(RentalRateRepository::class);
        $entityManager = $container->get(EntityManagerInterface::class);

        // Uses stable data from the test fixtures.
        $user = $userRepository->findOneBy([
            'email' => 'alice@test.fr',
        ]);

        $this->assertNotNull($user);

        $client->loginUser($user);

        $boatModel = $boatModelRepository->findOneBy([
            'slug' => 'escapade',
        ]);

        $this->assertNotNull($boatModel);

        $rentalRates = $rentalRateRepository->findActiveByBoatModel($boatModel);

        $this->assertNotEmpty($rentalRates);

        $rentalRate = $rentalRates[0];

        $date = new \DateTimeImmutable('2026-11-28');

        // Gets a valid CSRF token from the booking form.
        $crawler = $client->request('GET', '/reservation');

        $token = $crawler
            ->filter('input[name="_token"]')
            ->attr('value');

        $this->assertNotNull($token);

        // Mocks StripeService to avoid a real Stripe API call.
        $stripeService = $this->createMock(StripeService::class);

        $stripeService
            ->expects($this->once())
            ->method('createCheckoutSession')
            ->willReturn('https://checkout.stripe.test/session');

        $container->set(
            StripeService::class,
            $stripeService,
        );

        // Submits a valid booking form.
        $client->request(
            'POST',
            '/reservation',
            [
                '_token' => $token,
                'date' => $date->format('Y-m-d'),
                'passengerCount' => 2,
                'boatModel' => $boatModel->getId(),
                'rentalRate' => $rentalRate->getId(),
            ],
        );

        $this->assertResponseRedirects(
            'https://checkout.stripe.test/session',
        );

        // Applies the rental rate start time to the selected date.
        $startTime = $rentalRate->getStartTime();

        $startAt = $date->setTime(
            (int) $startTime->format('H'),
            (int) $startTime->format('i'),
        );

        // Checks that the booking was created.
        $booking = $entityManager
            ->getRepository(Booking::class)
            ->findOneBy([
                'user' => $user,
                'boatModel' => $boatModel,
                'rentalRate' => $rentalRate,
                'startAt' => $startAt,
            ]);

        $this->assertNotNull($booking);

        // Checks that the related payment was also created.
        $payment = $booking->getPayment();

        $this->assertNotNull($payment);

        $this->assertSame(
            Payment::STATUS_PENDING,
            $payment->getPaymentStatus(),
        );

        // Removes data created for this test.
        $entityManager->remove($payment);
        $entityManager->flush();

        $entityManager->remove($booking);
        $entityManager->flush();
    }
}