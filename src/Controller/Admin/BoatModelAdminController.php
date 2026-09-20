<?php

namespace App\Controller\Admin;

use App\Entity\BoatModel;
use App\Form\BoatModelFormType;
use App\Repository\BoatModelRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/modeles')]
class BoatModelAdminController extends AbstractController
{
    #[Route('', name: 'admin_boat_model_index', methods: ['GET'])]
    public function index(BoatModelRepository $boatModelRepository): Response
    {
        $boatModels = $boatModelRepository->findBy(
            [],
            ['capacity' => 'ASC']
        );

        return $this->render('admin/boat_model/index.html.twig', [
            'boatModels' => $boatModels,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_boat_model_edit', methods: ['GET', 'POST'])]
    public function edit(
        BoatModel $boatModel,
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {
        $form = $this->createForm(
            BoatModelFormType::class,
            $boatModel
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash(
                'success',
                'Le modèle a bien été modifié.'
            );

            return $this->redirectToRoute('admin_boat_model_index');
        }

        return $this->render('admin/boat_model/edit.html.twig', [
            'boatModel' => $boatModel,
            'form' => $form,
        ]);
    }
}