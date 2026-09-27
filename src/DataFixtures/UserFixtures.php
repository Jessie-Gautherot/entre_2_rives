<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Class UserFixtures
 *
 * Loads users into database.
 *
 * This fixture creates:
 * - One active client user
 * - One inactive client user
 * - One active administrator user
 */
class UserFixtures extends Fixture
{
    /**
     * Constructor
     *
     * @param UserPasswordHasherInterface $passwordHasher Service used to hash passwords
     */
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    /**
     * Load users into database
     *
     * @param ObjectManager $manager Doctrine entity manager
     *
     * @return void
     */
    public function load(ObjectManager $manager): void
    {
        // Create active client user
        $activeClient = new User();

        $activeClient->setFirstName('Alice');
        $activeClient->setLastName('Martin');
        $activeClient->setEmail('alice@test.fr');
        $activeClient->setPhone('06 10 20 30 40');

        // Hash password before saving
        $hashedPassword = $this->passwordHasher->hashPassword(
            $activeClient,
            'Client123!'
        );

        $activeClient->setPassword($hashedPassword);
        $activeClient->setActive(true);
        $activeClient->setActivationToken(null);

        $manager->persist($activeClient);

        // Create inactive client user
        $inactiveClient = new User();

        $inactiveClient->setFirstName('Lucas');
        $inactiveClient->setLastName('Bernard');
        $inactiveClient->setEmail('lucas@test.fr');
        $inactiveClient->setPhone('06 11 22 33 44');

        // Hash password before saving
        $hashedPassword = $this->passwordHasher->hashPassword(
            $inactiveClient,
            'Client456!'
        );

        $inactiveClient->setPassword($hashedPassword);
        $inactiveClient->setActive(false);
        $inactiveClient->setActivationToken(bin2hex(random_bytes(32)));

        $manager->persist($inactiveClient);

        // Create administrator user
        $admin = new User();

        $admin->setFirstName('Admin');
        $admin->setLastName('Admin');
        $admin->setEmail('admin@entre2rives.fr');
        $admin->setPhone('06 99 88 77 66');

        // Hash password before saving
        $hashedPassword = $this->passwordHasher->hashPassword(
            $admin,
            'Admin123!'
        );

        $admin->setPassword($hashedPassword);

        // Assign administrator role
        $admin->setRoles(['ROLE_ADMIN']);

        $admin->setActive(true);
        $admin->setActivationToken(null);

        $manager->persist($admin);

        // Save all users
        $manager->flush();
    }
}