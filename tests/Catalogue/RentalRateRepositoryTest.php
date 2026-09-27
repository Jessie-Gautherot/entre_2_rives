<?php

namespace App\Tests\Boat;

use App\Entity\RentalRate;
use App\Repository\BoatModelRepository;
use App\Repository\RentalRateRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class RentalRateRepositoryTest extends KernelTestCase
{
    /**
     * Delete the inactive test rate directly from the test database.
     */
    private function deleteTestRate(): void
    {
        $entityManager = static::getContainer()
            ->get(EntityManagerInterface::class);

        $entityManager->getConnection()->executeStatement(
            'DELETE FROM rental_rates WHERE label = :label',
            [
                'label' => 'Test Rate Inactive',
            ]
        );
    }

    /**
     * Check that only active rental rates belonging to the requested model are returned
     * and ordered by start time.
     */
    public function testFindActiveByBoatModel(): void
    {
        self::bootKernel();

        $entityManager = static::getContainer()
            ->get(EntityManagerInterface::class);

        $boatModelRepository = static::getContainer()
            ->get(BoatModelRepository::class);

        $rentalRateRepository = static::getContainer()
            ->get(RentalRateRepository::class);

        $this->deleteTestRate();

        // Get the Escapade model from the test fixtures.
        $escapade = $boatModelRepository->findOneBy([
            'slug' => 'escapade',
        ]);

        self::assertNotNull($escapade);

        // Add one inactive rental rate to the Escapade model.
        $inactiveRate = new RentalRate();
        $inactiveRate->setLabel('Test Rate Inactive');
        $inactiveRate->setDurationHours(2);
        $inactiveRate->setStartTime(new \DateTimeImmutable('10:00'));
        $inactiveRate->setPrice(5000);
        $inactiveRate->setIsActive(false);
        $inactiveRate->setBoatModel($escapade);

        $entityManager->persist($inactiveRate);
        $entityManager->flush();

        // Get the active rental rates for Escapade.
        $rates = $rentalRateRepository->findActiveByBoatModel($escapade);

        self::assertCount(7, $rates);

        // Check that all returned rates are active.
        foreach ($rates as $rate) {
            self::assertTrue($rate->isActive());
        }

        // Check that rates are ordered by start time.
        $startTimes = array_map(
            fn (RentalRate $rate) => $rate->getStartTime()->format('H:i'),
            $rates
        );

        self::assertSame(
            ['09:00', '09:00', '09:00', '11:00', '14:00', '14:00', '16:00'],
            $startTimes
        );

        $this->deleteTestRate();
    }
}