<?php

namespace App\Tests\Booking;

use App\Entity\Boat;
use App\Entity\Booking;
use App\Entity\Payment;
use App\Entity\RentalRate;
use App\Entity\User;
use App\Repository\BoatModelRepository;
use App\Repository\BoatRepository;
use App\Repository\RentalRateRepository;
use App\Repository\UserRepository;
use App\Service\BookingService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Tests booking creation and booking business rules.
 */
class BookingServiceTest extends KernelTestCase
{
    /**
     * Creates a confirmed booking used to block a boat during a test.
     */
    private function createBlockingBooking(
        User $user,
        Boat $boat,
        RentalRate $rentalRate,
        \DateTimeImmutable $date,
    ): Booking {
        $startTime = $rentalRate->getStartTime();
        $endTime = $rentalRate->getEndTime();

        $startAt = $date->setTime(
            (int) $startTime->format('H'),
            (int) $startTime->format('i'),
        );

        $endAt = $date->setTime(
            (int) $endTime->format('H'),
            (int) $endTime->format('i'),
        );

        $booking = new Booking();

        $booking->setUser($user);
        $booking->setBoatModel($boat->getBoatModel());
        $booking->setBoat($boat);
        $booking->setRentalRate($rentalRate);
        $booking->setRentalRateLabel($rentalRate->getLabel());
        $booking->setPassengerCount(1);
        $booking->setTotalPrice($rentalRate->getPrice());
        $booking->setStartAt($startAt);
        $booking->setEndAt($endAt);
        $booking->setStatus(Booking::STATUS_CONFIRMED);

        return $booking;
    }

    /**
     * Checks that a valid booking and its pending payment are created.
     */
    public function testMakeBooking(): void
    {
        self::bootKernel();

        $container = static::getContainer();

        $bookingService = $container->get(BookingService::class);
        $userRepository = $container->get(UserRepository::class);
        $boatModelRepository = $container->get(BoatModelRepository::class);
        $rentalRateRepository = $container->get(RentalRateRepository::class);
        $entityManager = $container->get(EntityManagerInterface::class);

        // Uses stable fixture data for a valid booking scenario.
        $user = $userRepository->findOneBy([
            'email' => 'alice@test.fr',
        ]);

        $this->assertNotNull($user);

        $boatModel = $boatModelRepository->findOneBy([
            'slug' => 'escapade',
        ]);

        $this->assertNotNull($boatModel);

        $rentalRates = $rentalRateRepository->findActiveByBoatModel($boatModel);

        $this->assertNotEmpty($rentalRates);

        $rentalRate = $rentalRates[0];

        $date = new \DateTimeImmutable('2026-11-21');
        $passengerCount = 2;

        $booking = $bookingService->makeBooking(
            $user,
            $boatModel,
            $rentalRate,
            $date,
            $passengerCount,
        );

        $this->assertSame($user, $booking->getUser());
        $this->assertSame($boatModel, $booking->getBoatModel());
        $this->assertSame($rentalRate, $booking->getRentalRate());
        $this->assertNotNull($booking->getBoat());
        $this->assertSame($passengerCount, $booking->getPassengerCount());
        $this->assertSame($rentalRate->getPrice(), $booking->getTotalPrice());

        $this->assertSame(
            Booking::STATUS_PENDING,
            $booking->getStatus(),
        );

        // Checks that the related pending payment was also created.
        $payment = $booking->getPayment();

        $this->assertNotNull($payment);

        $this->assertSame(
            Payment::STATUS_PENDING,
            $payment->getPaymentStatus(),
        );

        // Removes data created only for this test.
        $entityManager->remove($payment);
        $entityManager->flush();

        $entityManager->remove($booking);
        $entityManager->flush();
    }

    /**
     * Checks that an inactive user cannot create a booking.
     */
    public function testInactiveUserCannotBook(): void
    {
        self::bootKernel();

        $container = static::getContainer();

        $bookingService = $container->get(BookingService::class);
        $userRepository = $container->get(UserRepository::class);
        $boatModelRepository = $container->get(BoatModelRepository::class);
        $rentalRateRepository = $container->get(RentalRateRepository::class);

        // Uses the inactive client from the test fixtures.
        $user = $userRepository->findOneBy([
            'email' => 'lucas@test.fr',
        ]);

        $this->assertNotNull($user);

        $boatModel = $boatModelRepository->findOneBy([
            'slug' => 'escapade',
        ]);

        $this->assertNotNull($boatModel);

        $rentalRates = $rentalRateRepository->findActiveByBoatModel($boatModel);

        $this->assertNotEmpty($rentalRates);

        $rentalRate = $rentalRates[0];

        $date = new \DateTimeImmutable('2026-11-22');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'Votre compte doit être activé pour effectuer une réservation.'
        );

        $bookingService->makeBooking(
            $user,
            $boatModel,
            $rentalRate,
            $date,
            2,
        );
    }

    /**
     * Checks that a booking cannot be created
     * when no physical boat is available.
     */
    public function testBookingFailsWhenNoBoatIsAvailable(): void
    {
        self::bootKernel();

        $container = static::getContainer();

        $bookingService = $container->get(BookingService::class);
        $userRepository = $container->get(UserRepository::class);
        $boatModelRepository = $container->get(BoatModelRepository::class);
        $boatRepository = $container->get(BoatRepository::class);
        $rentalRateRepository = $container->get(RentalRateRepository::class);
        $entityManager = $container->get(EntityManagerInterface::class);

        // Uses valid fixture data before blocking every active boat.
        $user = $userRepository->findOneBy([
            'email' => 'alice@test.fr',
        ]);

        $this->assertNotNull($user);

        $boatModel = $boatModelRepository->findOneBy([
            'slug' => 'escapade',
        ]);

        $this->assertNotNull($boatModel);

        $rentalRates = $rentalRateRepository->findActiveByBoatModel($boatModel);

        $this->assertNotEmpty($rentalRates);

        $rentalRate = $rentalRates[0];

        $boats = $boatRepository->findActiveByBoatModel($boatModel);

        $this->assertNotEmpty($boats);

        $date = new \DateTimeImmutable('2026-11-25');

        $blockingBookings = [];

        // Blocks every active boat for the selected date and rental rate.
        foreach ($boats as $boat) {
            $booking = $this->createBlockingBooking(
                $user,
                $boat,
                $rentalRate,
                $date,
            );

            $entityManager->persist($booking);
            $blockingBookings[] = $booking;
        }

        $entityManager->flush();

        try {
            $bookingService->makeBooking(
                $user,
                $boatModel,
                $rentalRate,
                $date,
                2,
            );

            $this->fail(
                'The booking should fail when no boat is available.'
            );
        } catch (\LogicException $exception) {
            $this->assertSame(
                'Aucun bateau n’est disponible pour ce créneau.',
                $exception->getMessage(),
            );
        } finally {
            // Always removes temporary blocking bookings.
            foreach ($blockingBookings as $booking) {
                $entityManager->remove($booking);
            }

            $entityManager->flush();
        }
    }
}