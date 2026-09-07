<?php

namespace App\Repository;

use App\Entity\BoatModel;
use App\Entity\RentalRate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RentalRate>
 */
class RentalRateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RentalRate::class);
    }

    /**
     * Used to find active rental rates for a boat model for booking
     *
     * @return RentalRate[]
     */
    public function findActiveByBoatModel(BoatModel $boatModel): array
    {
        return $this->createQueryBuilder('rentalRate')
            ->andWhere('rentalRate.boatModel = :boatModel')
            ->andWhere('rentalRate.isActive = true')
            ->setParameter('boatModel', $boatModel)
            ->orderBy('rentalRate.startTime', 'ASC')
            ->getQuery()
            ->getResult();
    }
}