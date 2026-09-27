<?php

namespace App\Tests\User;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ActivationTest extends WebTestCase
{
    /**
     * Delete the test user directly from the test database.
     */
    private function deleteTestUser(): void
    {
        $entityManager = static::getContainer()
            ->get(EntityManagerInterface::class);

        $entityManager->getConnection()->executeStatement(
            'DELETE FROM users WHERE email = :email',
            ['email' => 'activation@test.fr']
        );
    }

    /**
     * Check that the test user can activate their account with a valid activation token.
     */
    public function testUserCanActivateAccount(): void
    {
        $client = static::createClient();

        $entityManager = static::getContainer()
            ->get(EntityManagerInterface::class);

        $this->deleteTestUser();

        // Create the test user as inactive with an activation token.
        $user = new User();
        $user->setFirstName('Sophie');
        $user->setLastName('Martin');
        $user->setPhone('06 12 34 56 78');
        $user->setEmail('activation@test.fr');
        $user->setPassword('fake-password');
        $user->setActive(false);
        $user->setActivationToken('activation-token-test');

        $entityManager->persist($user);
        $entityManager->flush();

        // Open the activation link.
        $client->request(
            'GET',
            '/activate/activation-token-test'
        );

        // Check the redirect to the login page.
        self::assertResponseRedirects('/login');

        // Get the test user.
        $userRepository = static::getContainer()
            ->get(UserRepository::class);

        $user = $userRepository->findOneBy([
            'email' => 'activation@test.fr',
        ]);

        // Check the activated account.
        self::assertNotNull($user);
        self::assertTrue($user->isActive());
        self::assertNull($user->getActivationToken());

        $this->deleteTestUser();
    }
}