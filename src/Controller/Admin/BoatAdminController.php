<?php

namespace App\Controller\Admin;

use App\Entity\Boat;
use App\Repository\BoatRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/bateaux')]
class BoatAdminController extends AbstractController
{
    #[Route('', name: 'admin_boat_index', methods: ['GET'])]
    public function index(BoatRepository $boatRepository): Response
    {
        $boats = $boatRepository->findBy(
            [],
            ['name' => 'ASC']
        );

        return $this->render('admin/boat/index.html.twig', [
            'boats' => $boats,
        ]);
    }

    #[Route('/{id}/disponibilite', name: 'admin_boat_toggle_availability', methods: ['POST'])]
    public function toggleAvailability(
        Boat $boat,
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {
        if (!$this->isCsrfTokenValid(
            'toggle_boat_availability_' . $boat->getId(),
            $request->request->get('_token')
        )) {
            throw $this->createAccessDeniedException(
                'Jeton CSRF invalide.'
            );
        }

        $boat->setIsActive(!$boat->isActive());

        $entityManager->flush();

        if ($boat->isActive()) {
            $message = 'Le bateau est maintenant disponible.';
        } else {
            $message = 'Le bateau est maintenant indisponible.';
        }

        $this->addFlash(
            'success',
            $message
        );

        return $this->redirectToRoute('admin_boat_index');
    }
}