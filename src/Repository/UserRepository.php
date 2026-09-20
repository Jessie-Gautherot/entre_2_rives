<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Find a user by the activation token.
     */
    public function findOneByActivationToken(string $token): ?User
    {
        return $this->findOneBy([
            'activationToken' => $token,
        ]);
    }

    /**
     * Finds all client users for dashboard (and not admin account).
     *
     * @return User[]
     */
    public function findClients(): array
    {
        return $this->createQueryBuilder('user')
            ->andWhere('user.roles LIKE :role')
            ->setParameter('role', '%"ROLE_CLIENT"%')
            ->orderBy('user.lastName', 'ASC')
            ->addOrderBy('user.firstName', 'ASC')
            ->getQuery()
            ->getResult();
    }
}