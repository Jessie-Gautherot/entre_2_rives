<?php

namespace App\Controller\Admin;

use App\Repository\BookingRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class DashboardController extends AbstractController
{
    #[Route('/admin', name: 'admin_dashboard', methods: ['GET'])]
    public function index(BookingRepository $bookingRepository): Response
    {
        // « Le contrôleur récupère les réservations du jour dans le repository,
        // les stocke dans $bookings, puis les transmet à la vue. »
        $bookings = $bookingRepository->findTodayBookings();

        return $this->render('admin/dashboard/index.html.twig', [
            'bookings' => $bookings,
        ]);
    }
}