<?php

namespace App\Repository;

use App\Entity\Boat;
use App\Entity\BoatModel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Boat>
 */
class BoatRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Boat::class);
    }

    /** 
     * Used to find active boats belonging to a specific model for booking
     *
     * @return Boat[]
     */
    public function findActiveByBoatModel(BoatModel $boatModel): array
    {
        return $this->createQueryBuilder('boat')
            ->andWhere('boat.boatModel = :boatModel')
            ->andWhere('boat.isActive = true')
            ->setParameter('boatModel', $boatModel)
            ->orderBy('boat.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}