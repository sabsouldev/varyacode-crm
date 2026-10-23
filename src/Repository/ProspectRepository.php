<?php

namespace App\Repository;

use App\Entity\Prospect;
use App\Entity\Tache;
use App\Enum\Priorite;
use App\Enum\Statut;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Prospect>
 */
class ProspectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Prospect::class);
    }

    /**
     * Prospects encore en cours (ni gagnés ni perdus) sans aucune tâche ouverte :
     * règle « une fiche ne reste jamais sans prochaine date ».
     *
     * @return list<Prospect>
     */
    public function findSansProchaineAction(): array
    {
        $sousRequete = $this->getEntityManager()->createQueryBuilder()
            ->select('1')
            ->from(Tache::class, 't')
            ->where('t.prospect = p')
            ->andWhere('t.faiteLe IS NULL')
            ->andWhere('t.annuleeLe IS NULL')
            ->getDQL();

        return $this->createQueryBuilder('p')
            ->where('p.statut NOT IN (:clos)')
            ->andWhere('p.optOut = false')
            ->andWhere(\sprintf('NOT EXISTS (%s)', $sousRequete))
            ->setParameter('clos', [Statut::GAGNE->value, Statut::PERDU->value])
            ->addSelect('CASE WHEN p.priorite = :haute THEN 0 WHEN p.priorite = :moyenne THEN 1 ELSE 2 END AS HIDDEN rangPriorite')
            ->setParameter('haute', Priorite::HAUTE)
            ->setParameter('moyenne', Priorite::MOYENNE)
            ->orderBy('rangPriorite')
            ->getQuery()
            ->getResult();
    }

    public function findOneByNomInsensible(string $nom): ?Prospect
    {
        return $this->createQueryBuilder('p')
            ->where('LOWER(p.nom) = LOWER(:nom)')
            ->setParameter('nom', trim($nom))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
