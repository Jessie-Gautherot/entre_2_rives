<?php

namespace App\Controller;

use App\Repository\BoatModelRepository;
use App\Service\AvailabilityService;
use App\Service\RentalRateDisplayService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class BoatController extends AbstractController
{
    #[Route('/bateaux', name: 'app_boat_index')]
    public function index(BoatModelRepository $boatModelRepository): Response
    {
        $boatModels = $boatModelRepository->findAll();

        return $this->render('boat/index.html.twig', [
            'boatModels' => $boatModels,
        ]);
    }

    /**
     * Returns the available boat models as JSON for the dynamic catalogue search.
     *
     * Called by JavaScript.
     */
    #[Route('/bateaux/recherche', name: 'app_boat_search')]
    public function search(
        Request $request,
        AvailabilityService $availabilityService,
    ): JsonResponse {
        $dateValue = $request->query->get('date');
        $passengerCount = $request->query->getInt('passengerCount');

        if (!$dateValue || $passengerCount < 1) {
            return $this->json(
                ['error' => 'La date et le nombre de passagers sont obligatoires.'],
                Response::HTTP_BAD_REQUEST,
            );
        }

        try {
            $date = new \DateTimeImmutable($dateValue);
        } catch (\Exception) {
            return $this->json(
                ['error' => 'La date est invalide.'],
                Response::HTTP_BAD_REQUEST,
            );
        }

        $boatModels = $availabilityService->findAvailableBoatModels(
            $date,
            $passengerCount,
        );

        $results = [];

        foreach ($boatModels as $boatModel) {
            $results[] = [
                'name' => $boatModel->getName(),
                'slug' => $boatModel->getSlug(),
                'capacity' => $boatModel->getCapacity(),
                'description' => $boatModel->getDescription(),
                'mainImage' => $boatModel->getMainImage(),
            ];
        }

        return $this->json($results);
    }

    /**
    * Displays the details of a boat model.
    */
    #[Route('/bateaux/{slug}', name: 'app_boat_show')]
    public function show(
        string $slug,
        BoatModelRepository $boatModelRepository,
        RentalRateDisplayService $rentalRateDisplayService,
    ): Response {
        $boatModel = $boatModelRepository->findOneBy([
            'slug' => $slug,
        ]);

        if (!$boatModel) {
            throw $this->createNotFoundException(
                'Ce bateau n’existe pas.'
            );
        }

        $groupedRates = $rentalRateDisplayService->groupByDuration(
            $boatModel->getRentalRates()->toArray()
        );

        return $this->render('boat/show.html.twig', [
            'boatModel' => $boatModel,
            'groupedRates' => $groupedRates,
        ]);
    }
}