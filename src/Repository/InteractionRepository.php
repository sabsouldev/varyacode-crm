<?php

namespace App\Repository;

use App\Entity\Interaction;
use App\Enum\Source;
use App\Enum\TypeInteraction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Interaction>
 */
class InteractionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Interaction::class);
    }

    /**
     * Nombre de prospects distincts contactés (message ou appel) sur la période.
     */
    public function compterProspectsContactes(\DateTimeImmutable $debut, \DateTimeImmutable $fin, ?Source $source = null): int
    {
        $qb = $this->createQueryBuilder('i')
            ->select('COUNT(DISTINCT p.id)')
            ->join('i.prospect', 'p')
            ->where('i.date >= :debut')
            ->andWhere('i.date < :fin')
            ->andWhere('i.type IN (:types)')
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->setParameter('types', [TypeInteraction::MESSAGE, TypeInteraction::APPEL]);

        if (null !== $source) {
            $qb->andWhere('p.source = :source')->setParameter('source', $source);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
