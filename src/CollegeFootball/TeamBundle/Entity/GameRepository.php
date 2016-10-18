<?php

namespace CollegeFootball\TeamBundle\Entity;

use Doctrine\ORM\EntityRepository;

use CollegeFootball\AppBundle\Entity\Week;

class GameRepository extends EntityRepository
{
    public function findGamesByTeam(Team $team)
    {
        $queryResult = $this->createQueryBuilder('g')
            ->where('g.homeTeam = :team OR g.awayTeam = :team')
            ->orderBy('g.date', 'asc')
            ->setParameter('team', $team)
            ->getQuery()
            ->getResult();

        return $queryResult;
    }

    public function findGamesByWeek(Week $week)
    {
        $games = $this->createQueryBuilder('g')
            ->where('g.date >= :startDate')
            ->andWhere('g.date <= :endDate')
            ->orderBy('g.date, g.time')
            ->setParameter('startDate', $week->getStartDate())
            ->setParameter('endDate', $week->getEndDate())
            ->getQuery()
            ->getResult();

        return $games;
    }
}
