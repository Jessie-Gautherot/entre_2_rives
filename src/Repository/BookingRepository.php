<?php

namespace App\Repository;

use App\Entity\Boat;
use App\Entity\Booking;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Booking>
 */
class BookingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Booking::class);
    }

    /**
     * Checks whether a boat already has a blocking booking
     * overlapping the requested time slot.
     */
    public function hasBlockingBooking(
        Boat $boat,
        \DateTimeImmutable $startAt,
        \DateTimeImmutable $endAt,
    ): bool {
        $count = $this->createQueryBuilder('booking')
            ->select('COUNT(booking.id)')
            ->andWhere('booking.boat = :boat')
            ->andWhere('booking.status IN (:blockingStatuses)')
            ->andWhere('booking.startAt < :endAt')
            ->andWhere('booking.endAt > :startAt')
            ->setParameter('boat', $boat)
            ->setParameter('blockingStatuses', [
                Booking::STATUS_PENDING,
                Booking::STATUS_CONFIRMED,
                Booking::STATUS_CANCELLATION_PENDING,
            ])
            ->setParameter('startAt', $startAt)
            ->setParameter('endAt', $endAt)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    /**
     * Returns today's bookings for admin dashboard.
     */
    public function findTodayBookings(): array
    {
        $startOfDay = new \DateTimeImmutable('today');
        $endOfDay = $startOfDay->modify('+1 day');

        return $this->createQueryBuilder('booking')
            ->andWhere('booking.startAt >= :startOfDay')
            ->andWhere('booking.startAt < :endOfDay')
            ->setParameter('startOfDay', $startOfDay)
            ->setParameter('endOfDay', $endOfDay)
            ->orderBy('booking.startAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}