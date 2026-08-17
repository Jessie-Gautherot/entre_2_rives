<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Handle user account activation.
 */
class UserActivationService
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Generate and assign an activation token to a user.
     */
    public function generateToken(User $user): void
    {
        $user->setActivationToken(bin2hex(random_bytes(32)));
    }

    /**
     * Activate a user account from the activation token
     */
    public function activate(string $token): bool
    {
        $user = $this->userRepository->findOneByActivationToken($token);

        if ($user === null) {
            return false;
        }

        $user->setActive(true);
        $user->setActivationToken(null);

        $this->entityManager->flush();

        return true;
    }
}