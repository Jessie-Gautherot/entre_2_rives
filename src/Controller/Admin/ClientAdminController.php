<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/clients')]
class ClientAdminController extends AbstractController
{
    #[Route('', name: 'admin_client_index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        $clients = $userRepository->findClients();

        return $this->render('admin/client/index.html.twig', [
            'clients' => $clients,
        ]);
    }

    #[Route('/{id}', name: 'admin_client_show', methods: ['GET'])]
    public function show(User $client): Response
    {
        return $this->render('admin/client/show.html.twig', [
            'client' => $client,
        ]);
    }
}