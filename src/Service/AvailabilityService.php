<?php

namespace App\Service;

use App\Entity\Boat;
use App\Entity\BoatModel;
use App\Entity\RentalRate;
use App\Repository\BoatModelRepository;
use App\Repository\BoatRepository;
use App\Repository\BookingRepository;
use App\Repository\RentalRateRepository;

/**
 * Handles boat availability for bookings and catalogue searches.
 */
class AvailabilityService
{
    public function __construct(
        private BoatRepository $boatRepository,
        private BookingRepository $bookingRepository,
        private BoatModelRepository $boatModelRepository,
        private RentalRateRepository $rentalRateRepository,
    ) {
    }

    /**
     * Used in booking creation to find an available physical boat for a model,
     * rental rate and selected date.
     */
    public function findAvailableBoat(
        BoatModel $boatModel,
        RentalRate $rentalRate,
        \DateTimeImmutable $date,
    ): ?Boat {
        $startTime = $rentalRate->getStartTime();
        $endTime = $rentalRate->getEndTime();

        // Applies the rental rate times to the selected date.
        $startAt = $date->setTime(
            (int) $startTime->format('H'),
            (int) $startTime->format('i'),
        );

        $endAt = $date->setTime(
            (int) $endTime->format('H'),
            (int) $endTime->format('i'),
        );

        $boats = $this->boatRepository->findActiveByBoatModel($boatModel);

        foreach ($boats as $boat) {
            $hasConflict = $this->bookingRepository->hasBlockingBooking(
                $boat,
                $startAt,
                $endAt,
            );

            if (!$hasConflict) {
                return $boat;
            }
        }

        return null;
    }

    /**
     * Used for catalogue filtering to find boat models with at least
     * one available rental rate for a selected date and passenger count.
     *
     * @return BoatModel[]
     */
    public function findAvailableBoatModels(
        \DateTimeImmutable $date,
        int $passengerCount,
    ): array {
        $boatModels = $this->boatModelRepository
            ->findByMinimumCapacity($passengerCount);

        $availableBoatModels = [];

        foreach ($boatModels as $boatModel) {
            $availableRentalRates = $this->findAvailableRentalRates(
                $boatModel,
                $date,
            );

            if ($availableRentalRates !== []) {
                $availableBoatModels[] = $boatModel;
            }
        }

        return $availableBoatModels;
    }

    /**
     * Used for booking to find and display available rental rates
     * for a boat model and selected date.
     *
     * @return RentalRate[]
     */
    public function findAvailableRentalRates(
        BoatModel $boatModel,
        \DateTimeImmutable $date,
    ): array {
        $rentalRates = $this->rentalRateRepository
            ->findActiveByBoatModel($boatModel);

        $availableRentalRates = [];

        foreach ($rentalRates as $rentalRate) {
            $availableBoat = $this->findAvailableBoat(
                $boatModel,
                $rentalRate,
                $date,
            );

            if ($availableBoat !== null) {
                $availableRentalRates[] = $rentalRate;
            }
        }

        return $availableRentalRates;
    }
}
