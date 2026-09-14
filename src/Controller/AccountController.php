<?php

namespace App\Controller;

use App\Repository\BookingRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class AccountController extends AbstractController
{
    /**
     * Displays the user's account information and bookings.
     */
    #[Route('/mon-compte', name: 'app_account')]
    public function index(BookingRepository $bookingRepository): Response
    {
        $user = $this->getUser();

        $bookings = $bookingRepository->findBy(
            ['user' => $user],
            ['startAt' => 'ASC']
        );

        return $this->render('account/index.html.twig', [
            'user' => $user,
            'bookings' => $bookings,
        ]);
    }
}