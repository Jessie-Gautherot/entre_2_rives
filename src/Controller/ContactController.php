<?php

namespace App\Controller;

use App\Form\ContactFormType;
use App\Service\EmailService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Attribute\Route;

class ContactController extends AbstractController
{
    #[Route('/contact', name: 'app_contact', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        EmailService $emailService
    ): Response {
        $form = $this->createForm(ContactFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            try {
                $emailService->sendContactEmail(
                    $data['name'],
                    $data['email'],
                    $data['subject'],
                    $data['message']
                );
            } catch (TransportExceptionInterface $exception) {
                $this->addFlash(
                    'danger',
                    'Une erreur est survenue lors de l’envoi du message. Veuillez réessayer.'
                );

                return $this->redirectToRoute('app_contact');
            }

            $this->addFlash(
                'success',
                'Votre message a bien été envoyé.'
            );

            return $this->redirectToRoute('app_contact');
        }

        return $this->render('contact/index.html.twig', [
            'contactForm' => $form,
        ]);
    }
}