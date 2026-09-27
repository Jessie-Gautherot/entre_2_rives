<?php

namespace App\Controller\Admin;

use App\Entity\RentalRate;
use App\Form\RentalRateFormType;
use App\Repository\RentalRateRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/tarifs')]
class RentalRateAdminController extends AbstractController
{
    #[Route('', name: 'admin_rental_rate_index', methods: ['GET'])]
    public function index(RentalRateRepository $rentalRateRepository): Response
    {
        $rentalRates = $rentalRateRepository->findBy(
            [],
            [
                'boatModel' => 'ASC',
                'durationHours' => 'ASC',
                'startTime' => 'ASC',
            ]
        );

        $rentalRatesByModel = [];

        foreach ($rentalRates as $rentalRate) {
            $modelName = $rentalRate->getBoatModel()->getName();

            $rentalRatesByModel[$modelName][] = $rentalRate;
        }

        return $this->render('admin/rental_rate/index.html.twig', [
            'rentalRatesByModel' => $rentalRatesByModel,
        ]);
    }

    #[Route('/creer', name: 'admin_rental_rate_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {
        $rentalRate = new RentalRate();

        $form = $this->createForm(
            RentalRateFormType::class,
            $rentalRate
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($rentalRate);
            $entityManager->flush();

            $this->addFlash(
                'success',
                'La formule a bien été créée.'
            );

            return $this->redirectToRoute('admin_rental_rate_index');
        }

        return $this->render('admin/rental_rate/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}/modifier', name: 'admin_rental_rate_edit', methods: ['GET', 'POST'])]
    public function edit(
        RentalRate $rentalRate,
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {
        $form = $this->createForm(
            RentalRateFormType::class,
            $rentalRate
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash(
                'success',
                'La formule a bien été modifiée.'
            );

            return $this->redirectToRoute('admin_rental_rate_index');
        }

        return $this->render('admin/rental_rate/edit.html.twig', [
            'form' => $form,
            'rentalRate' => $rentalRate,
        ]);
    }

    #[Route('/{id}/disponibilite', name: 'admin_rental_rate_toggle_availability', methods: ['POST'])]
    public function toggleAvailability(
        RentalRate $rentalRate,
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {
        if (!$this->isCsrfTokenValid(
            'toggle_rental_rate_' . $rentalRate->getId(),
            $request->request->get('_token')
        )) {
            throw $this->createAccessDeniedException(
                'Jeton CSRF invalide.'
            );
        }

        $rentalRate->setIsActive(!$rentalRate->isActive());

        $entityManager->flush();

        if ($rentalRate->isActive()) {
            $message = 'La formule est maintenant disponible.';
        } else {
            $message = 'La formule est maintenant indisponible.';
        }

        $this->addFlash(
            'success',
            $message
        );

        return $this->redirectToRoute('admin_rental_rate_index');
    }
}