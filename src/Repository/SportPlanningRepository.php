<?php

namespace App\Repository;

use App\Entity\SportPlanning;
use DateInterval;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SportPlanningRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SportPlanning::class);
    }

    public function getLessThanADay() {

        $qb = $this->createQueryBuilder('p')
            ->where(':nowPlus24Hours > p.startingDateTime')
            ->andWhere('p.place IS NULL')
            ->setParameter('nowPlus24Hours', (new DateTime())->add(new DateInterval('P1D')));
        $query = $qb->getQuery();

        return $query->execute();
    }
    public function getNextWeekSessionsByPromotion($promotion)
    {
        $now = new DateTime();
        $nextWeek = (clone $now)->add(new DateInterval('P7D'));

        $qb = $this->createQueryBuilder('p')
            ->where('p.startingDateTime >= :now')
            ->andWhere('p.startingDateTime < :nextWeek')
            ->setParameters([
                'now' => $now,
                'nextWeek' => $nextWeek
            ]);

        $query = $qb->getQuery();
        $results = $query->getResult();

        return array_filter($results, function ($result) use ($promotion) {

            return in_array($promotion, $result->getPromotion());
        });
    }
    public function save(SportPlanning $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(SportPlanning $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
