<?php

namespace App\Repository;

use App\Entity\Booking;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Booking>
 */
class BookingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Booking::class);
    }

    /**
     * @return list<Booking>
     */
    public function findPendingStartingBefore(\DateTimeImmutable $deadline): array
    {
        return $this->createQueryBuilder('b')
            ->innerJoin('b.session', 's')
            ->andWhere('b.status = :status')
            ->andWhere('s.startDate <= :deadline')
            ->setParameter('status', Booking::STATUS_PENDING)
            ->setParameter('deadline', $deadline)
            ->getQuery()
            ->getResult();
    }
}
