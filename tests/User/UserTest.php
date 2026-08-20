<?php

namespace App\Tests\User;

use App\Entity\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserTest extends TestCase
{
    /**
     * Create a user with valid data.
     */
    private function createValidUser(): User
    {
        $user = new User();

        $user->setEmail('sophie@test.fr');
        $user->setFirstName('Sophie');
        $user->setLastName('Martin');
        $user->setPhone('06 12 34 56 78');

        return $user;
    }

    /**
     * Create the Symfony validator.
     */
    private function createValidator(): ValidatorInterface
    {
        return Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    /**
     * Check that an invalid email address is rejected.
     */
    public function testInvalidEmail(): void
    {
        $user = $this->createValidUser();
        $user->setEmail('email-invalide');

        $errors = $this->createValidator()
            ->validateProperty($user, 'email');

        self::assertGreaterThan(0, count($errors));
    }

    /**
     * Check that a first name too short is rejected.
     */
    public function testFirstNameTooShort(): void
    {
        $user = $this->createValidUser();
        $user->setFirstName('A');

        $errors = $this->createValidator()
            ->validateProperty($user, 'firstName');

        self::assertGreaterThan(0, count($errors));
    }

    /**
     * Check that a last name too short is rejected.
     */
    public function testLastNameTooShort(): void
    {
        $user = $this->createValidUser();
        $user->setLastName('B');

        $errors = $this->createValidator()
            ->validateProperty($user, 'lastName');

        self::assertGreaterThan(0, count($errors));
    }

    /**
     * Check that an invalid phone number is rejected.
     */
    public function testInvalidPhone(): void
    {
        $user = $this->createValidUser();
        $user->setPhone('abcdefghij');

        $errors = $this->createValidator()
            ->validateProperty($user, 'phone');

        self::assertGreaterThan(0, count($errors));
    }
}