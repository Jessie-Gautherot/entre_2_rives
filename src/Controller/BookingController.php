<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Entity\User;
use App\Repository\BoatModelRepository;
use App\Repository\RentalRateRepository;
use App\Service\AvailabilityService;
use App\Service\BookingCancellationService;
use App\Service\BookingService;
use App\Service\StripeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class BookingController extends AbstractController
{
    /**
     * Displays the booking form.
     */
    #[Route('/reservation', name: 'app_booking_new', methods: ['GET'])]
    public function new(
        Request $request,
        BoatModelRepository $boatModelRepository,
    ): Response {
        return $this->render('booking/new.html.twig', [
            'boatModels' => $boatModelRepository->findAll(),
            'selectedDate' => $request->query->get('date'),
            'selectedPassengerCount' => $request->query->get('passengers'),
            'selectedModel' => $request->query->get('model'),
        ]);
    }

    /**
     * Returns the available rental rates for a boat model and date as JSON.
     *
     * Called by JavaScript to dynamically update the booking form.
     */
    #[Route(
        '/reservation/formules-disponibles',
        name: 'app_booking_available_rates',
        methods: ['GET'],
    )]
    public function availableRates(
        Request $request,
        BoatModelRepository $boatModelRepository,
        AvailabilityService $availabilityService,
    ): JsonResponse {
        $dateValue = $request->query->get('date');
        $modelId = $request->query->getInt('model');

        if (!$dateValue || $modelId < 1) {
            return $this->json(
                ['error' => 'La date et le modèle de bateau sont obligatoires.'],
                Response::HTTP_BAD_REQUEST,
            );
        }

        // Converts the date received from the request into a DateTimeImmutable object.
        try {
            $date = new \DateTimeImmutable($dateValue);
        } catch (\Exception) {
            return $this->json(
                ['error' => 'La date est invalide.'],
                Response::HTTP_BAD_REQUEST,
            );
        }

        $boatModel = $boatModelRepository->find($modelId);

        if ($boatModel === null) {
            return $this->json(
                ['error' => 'Le modèle de bateau est invalide.'],
                Response::HTTP_BAD_REQUEST,
            );
        }

        // Gets only the rental rates with at least one physical boat available.
        $rentalRates = $availabilityService->findAvailableRentalRates(
            $boatModel,
            $date,
        );

        // Keeps only the data needed by JavaScript to build the select options.
        $data = [];

        foreach ($rentalRates as $rentalRate) {
            $data[] = [
                'id' => $rentalRate->getId(),
                'label' => $rentalRate->getLabel(),
                'durationHours' => $rentalRate->getDurationHours(),
                'startTime' => $rentalRate->getStartTime()->format('H:i'),
                'endTime' => $rentalRate->getEndTime()->format('H:i'),
                'price' => $rentalRate->getPrice(),
            ];
        }

        return $this->json($data);
    }

    /**
     * Creates a booking from the submitted booking form.
     */
    #[Route('/reservation', name: 'app_booking_create', methods: ['POST'])]
    public function create(
        Request $request,
        BoatModelRepository $boatModelRepository,
        RentalRateRepository $rentalRateRepository,
        BookingService $bookingService,
        StripeService $stripeService,
    ): Response {
        // Checks the CSRF token before processing the submitted form.
        if (!$this->isCsrfTokenValid(
            'create_booking',
            $request->request->get('_token'),
        )) {
            throw $this->createAccessDeniedException(
                'Jeton CSRF invalide.',
            );
        }

        $dateValue = $request->request->get('date');
        $passengerCount = $request->request->getInt('passengerCount');
        $boatModelId = $request->request->getInt('boatModel');
        $rentalRateId = $request->request->getInt('rentalRate');
        $acceptTerms = $request->request->getBoolean('acceptTerms');

        // Checks that all required form data has been submitted.
        if (
            !$dateValue
            || $passengerCount < 1
            || $boatModelId < 1
            || $rentalRateId < 1
        ) {
            $this->addFlash(
                'danger',
                'Tous les champs sont obligatoires.',
            );

            return $this->redirectToRoute('app_booking_new');
        }

        // Checks that the rental conditions have been accepted.
        if (!$acceptTerms) {
            $this->addFlash(
                'danger',
                'Vous devez accepter les Conditions générales de location.',
            );

            return $this->redirectToRoute('app_booking_new');
        }

        // Converts the submitted date into a DateTimeImmutable object.
        try {
            $date = new \DateTimeImmutable($dateValue);
        } catch (\Exception) {
            $this->addFlash(
                'danger',
                'La date est invalide.',
            );

            return $this->redirectToRoute('app_booking_new');
        }

        $boatModel = $boatModelRepository->find($boatModelId);
        $rentalRate = $rentalRateRepository->find($rentalRateId);

        // Checks that the selected entities exist.
        if ($boatModel === null || $rentalRate === null) {
            $this->addFlash(
                'danger',
                'Le modèle ou la formule sélectionnée est invalide.',
            );

            return $this->redirectToRoute('app_booking_new');
        }

        /** @var User $user */
        $user = $this->getUser();

        // Delegates all booking business rules and creation to BookingService.
        try {
            $booking = $bookingService->makeBooking(
                $user,
                $boatModel,
                $rentalRate,
                $date,
                $passengerCount,
            );
        } catch (\LogicException $exception) {
            $this->addFlash(
                'danger',
                $exception->getMessage(),
            );

            return $this->redirectToRoute('app_booking_new');
        }

        // Creates the Stripe Checkout Session for the new booking.
        $checkoutUrl = $stripeService->createCheckoutSession($booking);

        // Redirects the customer to the Stripe Checkout page.
        return $this->redirect($checkoutUrl);
    }

    /**
     * Cancels a confirmed booking and requests its refund.
     */
    #[Route(
        '/reservation/{id}/annuler',
        name: 'app_booking_cancel',
        methods: ['POST'],
    )]
    public function cancel(
        Booking $booking,
        Request $request,
        BookingCancellationService $bookingCancellationService,
    ): Response {
        // Checks that the booking belongs to the connected user.
        if ($booking->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        // Checks the CSRF token before processing the cancellation.
        if (!$this->isCsrfTokenValid(
            'cancel-booking-' . $booking->getId(),
            $request->request->get('_token'),
        )) {
            throw $this->createAccessDeniedException(
                'Jeton CSRF invalide.',
            );
        }

        try {
            $bookingCancellationService->cancel($booking);

            $this->addFlash(
                'success',
                'Votre demande d’annulation a bien été prise en compte.',
            );
        } catch (\LogicException $exception) {
            $this->addFlash(
                'danger',
                $exception->getMessage(),
            );
        }

        return $this->redirectToRoute('app_account');
    }
}