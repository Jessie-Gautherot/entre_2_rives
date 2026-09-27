<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LegalController extends AbstractController
{
    #[Route('/conditions-generales-location', name: 'app_rental_conditions', methods: ['GET'])]
    public function rentalConditions(): Response
    {
        return $this->render('legal/rental_conditions.html.twig');
    }

    #[Route('/mentions-legales', name: 'app_legal_notice', methods: ['GET'])]
    public function legalNotice(): Response
    {
        return $this->render('legal/legal_notice.html.twig');
    }

    #[Route('/politique-de-confidentialite', name: 'app_privacy_policy', methods: ['GET'])]
    public function privacyPolicy(): Response
    {
        return $this->render('legal/privacy_policy.html.twig');
    }
}