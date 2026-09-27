<?php

namespace App\Tests\User;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class LoginTest extends WebTestCase
{
    /**
     * Delete the test user directly from the test database.
     */
    private function deleteTestUser(string $email): void
    {
        $entityManager = static::getContainer()
            ->get(EntityManagerInterface::class);

        $entityManager->getConnection()->executeStatement(
            'DELETE FROM users WHERE email = :email',
            ['email' => $email]
        );
    }

    /**
     * Create an active test user with a hashed password.
     */
    private function createActiveUser(
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): void {
        $user = new User();

        $user->setFirstName('Sophie');
        $user->setLastName('Martin');
        $user->setPhone('06 12 34 56 78');
        $user->setEmail('sophie@test.fr');
        $user->setActive(true);

        $user->setPassword(
            $passwordHasher->hashPassword(
                $user,
                'Password1!'
            )
        );

        $entityManager->persist($user);
        $entityManager->flush();
    }

    /**
     * Check that the test user can log in with valid credentials.
     */
    public function testUserCanLoginWithValidCredentials(): void
    {
        $client = static::createClient();

        $entityManager = static::getContainer()
            ->get(EntityManagerInterface::class);

        $passwordHasher = static::getContainer()
            ->get(UserPasswordHasherInterface::class);

        $this->deleteTestUser('sophie@test.fr');

        // Create the test user as active.
        $this->createActiveUser(
            $entityManager,
            $passwordHasher
        );

        $crawler = $client->request('GET', '/login');

        self::assertResponseIsSuccessful();

        // Get the CSRF token from the form.
        $csrfToken = $crawler
            ->filter('input[name="_csrf_token"]')
            ->attr('value');

        // Submit valid credentials.
        $client->request('POST', '/login', [
            'email' => 'sophie@test.fr',
            'password' => 'Password1!',
            '_csrf_token' => $csrfToken,
        ]);

        self::assertResponseRedirects('/');

        $this->deleteTestUser('sophie@test.fr');
    }

    /**
     * Check that the test user cannot log in with invalid password.
     */
    public function testUserCannotLoginWithInvalidPassword(): void
    {
        $client = static::createClient();

        $entityManager = static::getContainer()
            ->get(EntityManagerInterface::class);

        $passwordHasher = static::getContainer()
            ->get(UserPasswordHasherInterface::class);

        $this->deleteTestUser('sophie@test.fr');

        $this->createActiveUser(
            $entityManager,
            $passwordHasher
        );

        $crawler = $client->request('GET', '/login');

        self::assertResponseIsSuccessful();

        $csrfToken = $crawler
            ->filter('input[name="_csrf_token"]')
            ->attr('value');

        // Use an invalid password.
        $client->request('POST', '/login', [
            'email' => 'sophie@test.fr',
            'password' => 'WrongPassword1!',
            '_csrf_token' => $csrfToken,
        ]);

        // Symfony must reject the login and redirect to the login page.
        self::assertResponseRedirects('/login');

        $this->deleteTestUser('sophie@test.fr');
    }
}