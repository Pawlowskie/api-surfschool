<?php

namespace App\Repository;

use App\Entity\RefreshToken;
use DateTime;
use DateTimeInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Gesdinet\JWTRefreshTokenBundle\Doctrine\RefreshTokenRepositoryInterface;

class RefreshTokenRepository extends ServiceEntityRepository implements RefreshTokenRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RefreshToken::class);
    }

    public function revokeForUserIdentifier(string $userIdentifier): int
    {
        return $this->createQueryBuilder('rt')
            ->delete()
            ->where('rt.username = :username')
            ->setParameter('username', $userIdentifier)
            ->getQuery()
            ->execute();
    }

    /**
     * @return RefreshToken[]
     */
    public function findInvalid($datetime = null): array
    {
        $datetime = (null === $datetime) ? new DateTime() : $datetime;

        return $this->createQueryBuilder('rt')
            ->where('rt.valid < :datetime')
            ->setParameter(':datetime', $datetime)
            ->getQuery()
            ->getResult();
    }
}
