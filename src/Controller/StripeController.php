<?php

namespace App\Controller;

use App\Service\StripeService;
use Stripe\Checkout\Session;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Refund;
use Stripe\Webhook;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use UnexpectedValueException;

class StripeController extends AbstractController
{
    /**
     * Handles the customer return after a successful Stripe Checkout.
     */
    #[Route('/stripe/succes', name: 'app_stripe_success', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function success(): Response
    {
        $this->addFlash(
            'success',
            'Votre paiement est en cours de confirmation.'
        );

        return $this->redirectToRoute('app_account');
    }

    /**
     * Handles the customer return after a cancelled Stripe Checkout.
     */
    #[Route('/stripe/annulation', name: 'app_stripe_cancel', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function cancel(): Response
    {
        $this->addFlash(
            'danger',
            'Le paiement n’a pas été finalisé.'
        );

        return $this->redirectToRoute('app_booking_new');
    }

    /**
     * Receives and verifies Stripe webhook events.
     */
    #[Route('/stripe/webhook', name: 'app_stripe_webhook', methods: ['POST'])]
    public function webhook(
        Request $request,
        StripeService $stripeService,
        #[Autowire('%env(STRIPE_WEBHOOK_SECRET)%')]
        string $webhookSecret,
    ): Response {
        $payload = $request->getContent();
        $signature = $request->headers->get('Stripe-Signature');

        if ($signature === null) {
            return new Response(status: Response::HTTP_BAD_REQUEST);
        }

        try {
            $event = Webhook::constructEvent(
                $payload,
                $signature,
                $webhookSecret
            );
        } catch (UnexpectedValueException | SignatureVerificationException) {
            return new Response(status: Response::HTTP_BAD_REQUEST);
        }

        if (
            $event->type === 'checkout.session.completed'
            && $event->data->object instanceof Session
        ) {
            $stripeService->handleCompletedCheckout($event->data->object);
        }

        if (
            $event->type === 'refund.created'
            && $event->data->object instanceof Refund
        ) {
            $stripeService->handleCreatedRefund($event->data->object);
        }

        if (
            $event->type === 'refund.failed'
            && $event->data->object instanceof Refund
        ) {
            $stripeService->handleFailedRefund($event->data->object);
        }

        return new Response(status: Response::HTTP_OK);
    }
}