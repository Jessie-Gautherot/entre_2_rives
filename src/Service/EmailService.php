<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

/**
 * Service used to send emails.
 */
class EmailService
{
    public function __construct(
        private MailerInterface $mailer,
        private string $appUrl,
        private string $mailerFrom,
        private string $contactEmail
    ) {
    }

    /**
     * Send an account activation email.
     */
    public function sendActivationEmail(User $user): void
    {
        $activationLink = $this->appUrl
            . '/activate/'
            . $user->getActivationToken();

        $email = (new TemplatedEmail())
            ->from($this->mailerFrom)
            ->to((string) $user->getEmail())
            ->subject('Activation de votre compte')
            ->htmlTemplate('emails/activation.html.twig')
            ->context([
                'user' => $user,
                'activationLink' => $activationLink,
            ]);

        $this->mailer->send($email);
    }

    /**
     * Send a contact form email.
     */
    public function sendContactEmail(
        string $name,
        string $email,
        string $subject,
        string $message
    ): void {
        $emailMessage = (new TemplatedEmail())
            ->from($this->mailerFrom)
            ->to($this->contactEmail)
            ->replyTo($email)
            ->subject('Contact - ' . $subject)
            ->htmlTemplate('emails/contact.html.twig')
            ->context([
                'name' => $name,
                'senderEmail' => $email,
                'subject' => $subject,
                'message' => $message,
            ]);

        $this->mailer->send($emailMessage);
    }
}