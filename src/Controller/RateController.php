<?php

namespace App\Controller;

use App\Repository\BoatModelRepository;
use App\Service\RentalRateDisplayService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class RateController extends AbstractController
{
    /**
    * Displays rental rates for all boat models.
    */
    #[Route('/tarifs', name: 'app_rate_index')]
    public function index(
        BoatModelRepository $boatModelRepository,
        RentalRateDisplayService $rentalRateDisplayService
    ): Response {
        $boatModels = $boatModelRepository->findAll();

        $pricing = $rentalRateDisplayService->prepareByBoatModel($boatModels);

        return $this->render('rate/index.html.twig', [
            'pricing' => $pricing,
        ]);
    }
}