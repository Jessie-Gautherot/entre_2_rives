<?php

namespace App\Tests\Boat;

use App\Repository\BoatModelRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class BoatModelRepositoryTest extends KernelTestCase
{
    /**
     * Check that boat models are filtered by minimum capacity
     * and ordered by capacity.
     */
    public function testFindByMinimumCapacity(): void
    {
        self::bootKernel();

        $boatModelRepository = static::getContainer()
            ->get(BoatModelRepository::class);

        // Get boat models for at least 6 passengers from the test fixtures.
        $boatModels = $boatModelRepository->findByMinimumCapacity(6);

        // Check that only models with enough capacity are returned.
        self::assertCount(2, $boatModels);
        self::assertSame('Évasion', $boatModels[0]->getName());
        self::assertSame('Grand Large', $boatModels[1]->getName());
    }
}