<?php

namespace App\Tests\User;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class RegistrationTest extends WebTestCase
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
            ['email' => 'registration@test.fr']
        );
    }

    /**
     * Create an existing test user in the test database.
     */
    private function createTestUser(): void
    {
        $entityManager = static::getContainer()
            ->get(EntityManagerInterface::class);

        $passwordHasher = static::getContainer()
            ->get(UserPasswordHasherInterface::class);

        $user = new User();
        $user->setFirstName('Sophie');
        $user->setLastName('Martin');
        $user->setPhone('06 12 34 56 78');
        $user->setEmail('registration@test.fr');
        // Hash the password before saving it.
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
     * Check that the test user can register with valid data.
     */
    public function testUserCanRegister(): void
    {
        $client = static::createClient();

        $this->deleteTestUser();

        // Open the registration page.
        $crawler = $client->request('GET', '/register');

        self::assertResponseIsSuccessful();

        $form = $crawler->filter('form')->form([
            'registration_form[firstName]' => 'Sophie',
            'registration_form[lastName]' => 'Martin',
            'registration_form[phone]' => '06 12 34 56 78',
            'registration_form[email]' => 'registration@test.fr',
            'registration_form[plainPassword][first]' => 'Password1!',
            'registration_form[plainPassword][second]' => 'Password1!',
            'registration_form[acceptTerms]' => '1',
        ]);

        $client->submit($form);

        self::assertResponseRedirects('/login');

        // Get the created user
        $userRepository = static::getContainer()
            ->get(UserRepository::class);

        $user = $userRepository->findOneBy([
            'email' => 'registration@test.fr',
        ]);

        // Check the created account
        self::assertNotNull($user);
        self::assertFalse($user->isActive());
        self::assertNotNull($user->getActivationToken());

        // Check the hashed password
        $passwordHasher = static::getContainer()
            ->get(UserPasswordHasherInterface::class);

        self::assertTrue(
            $passwordHasher->isPasswordValid(
                $user,
                'Password1!'
            )
        );

        $this->deleteTestUser();
    }

    /**
     * Check that the user cannot register twice with the same email address. 
     */
    public function testUserCannotRegisterWithExistingEmail(): void
    {
        $client = static::createClient();

        $this->deleteTestUser();

        $this->createTestUser();

        $crawler = $client->request('GET', '/register');

        self::assertResponseIsSuccessful();

        // Try to register again with the same email address.
        $form = $crawler->filter('form')->form([
            'registration_form[firstName]' => 'Sophie',
            'registration_form[lastName]' => 'Martin',
            'registration_form[phone]' => '06 12 34 56 78',
            'registration_form[email]' => 'registration@test.fr',
            'registration_form[plainPassword][first]' => 'Password1!',
            'registration_form[plainPassword][second]' => 'Password1!',
            'registration_form[acceptTerms]' => '1',
        ]);

        $client->submit($form);

        // The registration form must be rejected.
        self::assertResponseStatusCodeSame(422);

        self::assertSelectorTextContains(
            'body',
            'Cette adresse e-mail est déjà utilisée.'
        );

        // Check that only one user exists with this email address.
        $userRepository = static::getContainer()
            ->get(UserRepository::class);

        $users = $userRepository->findBy([
            'email' => 'registration@test.fr',
        ]);

        self::assertCount(1, $users);

        $this->deleteTestUser();
    }
}