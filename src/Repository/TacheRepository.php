<?php

namespace App\Repository;

use App\Entity\Tache;
use App\Enum\Priorite;
use App\Enum\TypeTache;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Tache>
 */
class TacheRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tache::class);
    }

    /**
     * Tâches ouvertes à faire au plus tard ce jour-là (retards compris).
     *
     * @return list<Tache>
     */
    public function findOuvertesJusquAu(\DateTimeImmutable $jour, ?TypeTache $type = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->addSelect('p')
            ->join('t.prospect', 'p')
            ->where('t.faiteLe IS NULL')
            ->andWhere('t.annuleeLe IS NULL')
            ->andWhere('t.echeance <= :jour')
            ->andWhere('p.optOut = false')
            ->setParameter('jour', $jour->setTime(0, 0))
            ->orderBy('t.echeance')
            ->addSelect('CASE WHEN p.priorite = :haute THEN 0 WHEN p.priorite = :moyenne THEN 1 ELSE 2 END AS HIDDEN rangPriorite')
            ->setParameter('haute', Priorite::HAUTE)
            ->setParameter('moyenne', Priorite::MOYENNE)
            ->addOrderBy('rangPriorite');

        if (null !== $type) {
            $qb->andWhere('t.type = :type')->setParameter('type', $type);
        }

        return $qb->getQuery()->getResult();
    }
}
