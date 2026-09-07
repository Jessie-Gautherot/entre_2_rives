<?php

namespace App\Repository;

use App\Entity\BoatModel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BoatModel>
 */
class BoatModelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BoatModel::class);
    }

    /**
     * Used to filter boat models by number of passengers.
     *
     * @return BoatModel[]
     */
    public function findByMinimumCapacity(int $capacity): array
    {
        return $this->createQueryBuilder('boatModel')
            ->andWhere('boatModel.capacity >= :capacity')
            ->setParameter('capacity', $capacity)
            ->orderBy('boatModel.capacity', 'ASC')
            ->getQuery()
            ->getResult();
    }
}