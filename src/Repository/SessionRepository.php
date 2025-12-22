<?php

namespace App\Repository;

use App\Entity\Session;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Session>
 */
class SessionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Session::class);
    }

    /**
     * @return list<Session>
     */
    public function findPastSessions(\DateTimeImmutable $reference): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('COALESCE(s.endDate, s.startDate) <= :reference')
            ->setParameter('reference', $reference)
            ->getQuery()
            ->getResult();
    }
}
