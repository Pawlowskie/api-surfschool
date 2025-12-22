<?php

namespace App\Repository;

use App\Entity\Booking;
use App\Enum\BookingStatus;
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
            ->setParameter('status', BookingStatus::Pending)
            ->setParameter('deadline', $deadline)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Booking>
     */
    public function findPendingStartingBetween(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        return $this->createQueryBuilder('b')
            ->innerJoin('b.session', 's')
            ->andWhere('b.status = :status')
            ->andWhere('s.startDate BETWEEN :from AND :to')
            ->setParameter('status', BookingStatus::Pending)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param list<BookingStatus> $statuses
     *
     * @return list<Booking>
     */
    public function findStartingBetweenWithStatuses(
        \DateTimeImmutable $from,
        \DateTimeImmutable $to,
        array $statuses
    ): array {
        return $this->createQueryBuilder('b')
            ->innerJoin('b.session', 's')
            ->andWhere('s.startDate BETWEEN :from AND :to')
            ->andWhere('b.status IN (:statuses)')
            ->andWhere('b.reminderSentAt IS NULL')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->setParameter('statuses', $statuses)
            ->getQuery()
            ->getResult();
    }
}
