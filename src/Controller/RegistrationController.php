<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Service\EmailService;
use App\Service\UserActivationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Handle user registration and account activation.
 */
class RegistrationController extends AbstractController
{
    /**
     * Display and handle the registration form.
     */
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        UserActivationService $userActivationService,
        EmailService $emailService
    ): Response {
        $user = new User();

        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Get the plain password from the form.
            $plainPassword = $form->get('plainPassword')->getData();

            // Hash the password before saving the user.
            $hashedPassword = $passwordHasher->hashPassword(
                $user,
                $plainPassword
            );

            $user->setPassword($hashedPassword);

            // Prepare account activation.
            $user->setActive(false);
            $userActivationService->generateToken($user);

            // Save the user 
            $entityManager->persist($user);
            $entityManager->flush();

            // Send the activation email.
            $emailService->sendActivationEmail($user);

            $this->addFlash(
                'success',
                'Votre compte a bien été créé. Veuillez consulter vos e-mails pour l’activer.'
            );

            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }

    /**
     * Activate a user account using its activation token.
     */
    #[Route('/activate/{token}', name: 'app_activate')]
    public function activate(
        string $token,
        UserActivationService $userActivationService
    ): Response {
        $isActivated = $userActivationService->activate($token);

        if (!$isActivated) {
            throw $this->createNotFoundException(
                'Le lien d’activation est invalide.'
            );
        }

        $this->addFlash(
            'success',
            'Votre compte est activé. Vous pouvez maintenant vous connecter.'
        );

        return $this->redirectToRoute('app_login');
    }
}