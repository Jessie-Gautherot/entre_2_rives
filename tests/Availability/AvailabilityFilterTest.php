<?php

namespace App\Tests\Availability;

use App\Entity\BoatModel;
use App\Entity\RentalRate;
use App\Repository\BoatModelRepository;
use App\Service\AvailabilityService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Tests availability filters for rental rates and boat models.
 */
class AvailabilityFilterTest extends KernelTestCase
{
    /**
     * Checks that available rental rates are returned
     * for a selected boat model and date.
     */
    public function testFindAvailableRentalRates(): void
    {
        self::bootKernel();

        $container = static::getContainer();

        $availabilityService = $container->get(AvailabilityService::class);
        $boatModelRepository = $container->get(BoatModelRepository::class);

        // Uses stable catalogue data from the test fixtures.
        $boatModel = $boatModelRepository->findOneBy([
            'slug' => 'escapade',
        ]);

        $this->assertNotNull($boatModel);

        $date = new \DateTimeImmutable('2026-11-17');

        $availableRentalRates = $availabilityService->findAvailableRentalRates(
            $boatModel,
            $date,
        );

        $this->assertNotEmpty($availableRentalRates);

        $this->assertInstanceOf(
            RentalRate::class,
            $availableRentalRates[0],
        );
    }

    /**
     * Checks that available boat models are returned
     * for a selected date and passenger count.
     */
    public function testFindAvailableBoatModels(): void
    {
        self::bootKernel();

        $container = static::getContainer();

        $availabilityService = $container->get(AvailabilityService::class);

        $date = new \DateTimeImmutable('2026-11-19');
        $passengerCount = 4;

        $availableBoatModels = $availabilityService->findAvailableBoatModels(
            $date,
            $passengerCount,
        );

        $this->assertNotEmpty($availableBoatModels);

        $this->assertInstanceOf(
            BoatModel::class,
            $availableBoatModels[0],
        );

        // Checks that every returned model has enough capacity.
        foreach ($availableBoatModels as $boatModel) {
            $this->assertGreaterThanOrEqual(
                $passengerCount,
                $boatModel->getCapacity(),
            );
        }
    }
}