<?php

namespace App\Tests\Boat;

use App\Entity\Boat;
use App\Repository\BoatModelRepository;
use App\Repository\BoatRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class BoatRepositoryTest extends KernelTestCase
{
    /**
     * Delete the inactive test boat directly from the test database.
     */
    private function deleteTestBoat(): void
    {
        $entityManager = static::getContainer()
            ->get(EntityManagerInterface::class);

        $entityManager->getConnection()->executeStatement(
            'DELETE FROM boats WHERE name = :name',
            [
                'name' => 'Test Boat Inactive',
            ]
        );
    }

    /**
     * Check that only active boats belonging to the requested model are returned.
     */
    public function testFindActiveByBoatModel(): void
    {
        self::bootKernel();

        $entityManager = static::getContainer()
            ->get(EntityManagerInterface::class);

        $boatModelRepository = static::getContainer()
            ->get(BoatModelRepository::class);

        $boatRepository = static::getContainer()
            ->get(BoatRepository::class);

        $this->deleteTestBoat();

        // Get the Escapade model from the test fixtures.
        $escapade = $boatModelRepository->findOneBy([
            'slug' => 'escapade',
        ]);

        self::assertNotNull($escapade);

        // Add one inactive boat to the Escapade model.
        $inactiveBoat = new Boat();
        $inactiveBoat->setName('Test Boat Inactive');
        $inactiveBoat->setIsActive(false);
        $inactiveBoat->setBoatModel($escapade);

        $entityManager->persist($inactiveBoat);
        $entityManager->flush();

        // Check that only active Escapade boats are returned.
        $boats = $boatRepository->findActiveByBoatModel($escapade);

        self::assertCount(3, $boats);
        self::assertSame('Escapade 1', $boats[0]->getName());
        self::assertSame('Escapade 2', $boats[1]->getName());
        self::assertSame('Escapade 3', $boats[2]->getName());

        $this->deleteTestBoat();
    }
}