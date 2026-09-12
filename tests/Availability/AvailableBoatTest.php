<?php

namespace App\Tests\Availability;

use App\Entity\Boat;
use App\Entity\Booking;
use App\Entity\RentalRate;
use App\Entity\User;
use App\Repository\BoatModelRepository;
use App\Repository\BoatRepository;
use App\Repository\RentalRateRepository;
use App\Repository\UserRepository;
use App\Service\AvailabilityService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Tests physical boat availability using the test database.
 */
class AvailableBoatTest extends KernelTestCase
{
    /**
     * Creates a booking used for availability test scenarios.
     */
    private function createBooking(
        User $user,
        Boat $boat,
        RentalRate $rentalRate,
        \DateTimeImmutable $startAt,
        \DateTimeImmutable $endAt,
        string $status = Booking::STATUS_CONFIRMED,
    ): Booking {
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
        $booking->setStatus($status);

        return $booking;
    }

    /**
     * Checks that an available boat is returned
     * when no booking blocks the selected date and time.
     */
    public function testFindAvailableBoat(): void
    {
        self::bootKernel();

        $container = static::getContainer();

        $availabilityService = $container->get(AvailabilityService::class);
        $boatModelRepository = $container->get(BoatModelRepository::class);
        $rentalRateRepository = $container->get(RentalRateRepository::class);

        // Uses stable catalogue data from the test fixtures.
        $boatModel = $boatModelRepository->findOneBy([
            'slug' => 'escapade',
        ]);

        $this->assertNotNull($boatModel);

        $rentalRates = $rentalRateRepository->findActiveByBoatModel($boatModel);

        $this->assertNotEmpty($rentalRates);

        $rentalRate = $rentalRates[0];

        $date = new \DateTimeImmutable('2026-11-15');

        $boat = $availabilityService->findAvailableBoat(
            $boatModel,
            $rentalRate,
            $date,
        );

        $this->assertInstanceOf(Boat::class, $boat);
        $this->assertSame($boatModel, $boat->getBoatModel());
    }

    /**
     * Checks that no boat is returned when all active boats
     * of the selected model are already booked.
     */
    public function testNoBoatAvailableWhenAllAreBooked(): void
    {
        self::bootKernel();

        $container = static::getContainer();

        $availabilityService = $container->get(AvailabilityService::class);
        $boatModelRepository = $container->get(BoatModelRepository::class);
        $rentalRateRepository = $container->get(RentalRateRepository::class);
        $boatRepository = $container->get(BoatRepository::class);
        $userRepository = $container->get(UserRepository::class);
        $entityManager = $container->get(EntityManagerInterface::class);

        // Uses stable data from the test fixtures.
        $boatModel = $boatModelRepository->findOneBy([
            'slug' => 'escapade',
        ]);

        $this->assertNotNull($boatModel);

        $rentalRates = $rentalRateRepository->findActiveByBoatModel($boatModel);

        $this->assertNotEmpty($rentalRates);

        $rentalRate = $rentalRates[0];

        $boats = $boatRepository->findActiveByBoatModel($boatModel);

        $this->assertNotEmpty($boats);

        $user = $userRepository->findOneBy([
            'isActive' => true,
        ]);

        $this->assertNotNull($user);

        $date = new \DateTimeImmutable('2026-10-16');

        $startTime = $rentalRate->getStartTime();
        $endTime = $rentalRate->getEndTime();

        // Applies the rental rate times to the selected test date.
        $startAt = $date->setTime(
            (int) $startTime->format('H'),
            (int) $startTime->format('i'),
        );

        $endAt = $date->setTime(
            (int) $endTime->format('H'),
            (int) $endTime->format('i'),
        );

        $bookings = [];

        // Books every active boat for the same date and time.
        foreach ($boats as $boat) {
            $booking = $this->createBooking(
                $user,
                $boat,
                $rentalRate,
                $startAt,
                $endAt,
            );

            $entityManager->persist($booking);
            $bookings[] = $booking;
        }

        $entityManager->flush();

        $availableBoat = $availabilityService->findAvailableBoat(
            $boatModel,
            $rentalRate,
            $date,
        );

        $this->assertNull($availableBoat);

        // Removes the bookings created for this test.
        foreach ($bookings as $booking) {
            $entityManager->remove($booking);
        }

        $entityManager->flush();
    }
}
     