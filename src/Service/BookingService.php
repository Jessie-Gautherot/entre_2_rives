<?php

namespace App\Service;

use App\Entity\BoatModel;
use App\Entity\Booking;
use App\Entity\Payment;
use App\Entity\RentalRate;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class BookingService
{
    public function __construct(
        private readonly AvailabilityService $availabilityService,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Checks whether a booking request respects the business rules.
     */
    private function checkBooking(
        User $user,
        BoatModel $boatModel,
        RentalRate $rentalRate,
        \DateTimeImmutable $date,
        int $passengerCount,
    ): void {
        if (!$user->isActive()) {
            throw new \LogicException(
                'Votre compte doit être activé pour effectuer une réservation.'
            );
        }

        if (!$rentalRate->isActive()) {
            throw new \LogicException(
                'Cette formule de location n’est plus disponible.'
            );
        }

        if ($rentalRate->getBoatModel() !== $boatModel) {
            throw new \LogicException(
                'La formule sélectionnée ne correspond pas à ce bateau.'
            );
        }

        if ($passengerCount <= 0) {
            throw new \LogicException(
                'Le nombre de passagers doit être supérieur à zéro.'
            );
        }

        if ($passengerCount > $boatModel->getCapacity()) {
            throw new \LogicException(
                'Le nombre de passagers dépasse la capacité de ce bateau.'
            );
        }

        $today = new \DateTimeImmutable('today');

        if ($date <= $today) {
            throw new \LogicException(
                'Les réservations doivent être effectuées au plus tard la veille.'
            );
        }

        $dayOfWeek = (int) $date->format('N');

        if ($dayOfWeek < 3) {
            throw new \LogicException(
                'Les locations sont disponibles du mercredi au dimanche.'
            );
        }
    }

    /**
     * Creates a booking and its pending payment.
     */
    public function makeBooking(
        User $user,
        BoatModel $boatModel,
        RentalRate $rentalRate,
        \DateTimeImmutable $date,
        int $passengerCount,
    ): Booking {
        // Check the booking request.
        $this->checkBooking(
            $user,
            $boatModel,
            $rentalRate,
            $date,
            $passengerCount,
        );

        // Find an available physical boat.
        $boat = $this->availabilityService->findAvailableBoat(
            $boatModel,
            $rentalRate,
            $date,
        );

        if ($boat === null) {
            throw new \LogicException(
                'Aucun bateau n’est disponible pour ce créneau.'
            );
        }

        // Build the booking period.
        $startAt = $date->setTime(
            (int) $rentalRate->getStartTime()->format('H'),
            (int) $rentalRate->getStartTime()->format('i'),
        );

        $endAt = $date->setTime(
            (int) $rentalRate->getEndTime()->format('H'),
            (int) $rentalRate->getEndTime()->format('i'),
        );

        // Create the booking.
        $booking = new Booking();

        $booking
            ->setUser($user)
            ->setBoatModel($boatModel)
            ->setBoat($boat)
            ->setRentalRate($rentalRate)
            ->setRentalRateLabel($rentalRate->getLabel())
            ->setPassengerCount($passengerCount)
            ->setTotalPrice($rentalRate->getPrice())
            ->setStartAt($startAt)
            ->setEndAt($endAt);

        // Create the pending payment.
        $payment = new Payment();

        $payment
            ->setBooking($booking)
            ->setAmount($rentalRate->getPrice());

        // Save the booking and payment.
        $this->entityManager->persist($booking);
        $this->entityManager->persist($payment);
        $this->entityManager->flush();

        return $booking;
    }
}