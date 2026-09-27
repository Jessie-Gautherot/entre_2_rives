<?php

namespace App\Controller\Admin;

use App\Entity\Booking;
use App\Repository\BookingRepository;
use App\Service\BookingCancellationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/reservations')]
class BookingAdminController extends AbstractController
{
    #[Route('', name: 'admin_booking_index', methods: ['GET'])]
    public function index(BookingRepository $bookingRepository): Response
    {
        $bookings = $bookingRepository->findBy(
            [],
            ['startAt' => 'ASC']
        );

        return $this->render('admin/booking/index.html.twig', [
            'bookings' => $bookings,
        ]);
    }

    #[Route('/{id}', name: 'admin_booking_show', methods: ['GET'])]
    public function show(Booking $booking): Response
    {
        return $this->render('admin/booking/show.html.twig', [
            'booking' => $booking,
        ]);
    }

    #[Route('/{id}/cancel', name: 'admin_booking_cancel', methods: ['POST'])]
    public function cancel(
        Booking $booking,
        Request $request,
        BookingCancellationService $bookingCancellationService,
    ): Response {
        if (!$this->isCsrfTokenValid(
            'cancel_booking_' . $booking->getId(),
            $request->request->get('_token')
        )) {
            throw $this->createAccessDeniedException(
                'Jeton CSRF invalide.'
            );
        }

        try {
            $bookingCancellationService->cancel($booking, true);

            $this->addFlash(
                'success',
                'La demande d’annulation a bien été prise en compte.'
            );
        } catch (\LogicException $exception) {
            $this->addFlash(
                'danger',
                $exception->getMessage()
            );
        }

        return $this->redirectToRoute('admin_booking_show', [
            'id' => $booking->getId(),
        ]);
    }
}