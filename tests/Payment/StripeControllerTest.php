<?php

namespace App\Tests\Payment;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class StripeControllerTest extends WebTestCase
{
    /**
     * Checks that a Stripe webhook without a signature is rejected.
     */
    public function testWebhookWithoutSignature(): void
    {
        $client = static::createClient();

        // Sends a webhook request without a Stripe signature.
        $client->request(
            'POST',
            '/stripe/webhook',
            server: [
                'CONTENT_TYPE' => 'application/json',
            ],
            content: '{}'
        );

        $this->assertResponseStatusCodeSame(400);
    }
}