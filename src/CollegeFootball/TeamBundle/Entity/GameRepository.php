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
        $query  = "
            SELECT * FROM game g
            WHERE g.date >= :startDate
            AND g.date <= :endDate
            ORDER BY g.date, STR_TO_DATE(g.time, '%h.%i%p')
        ";

        $em        = $this->getEntityManager();
        $statement = $em->getConnection()->prepare($query);
        $statement->bindValue('startDate', $week->getStartDate()->format('Y-m-d'));
        $statement->bindValue('endDate', $week->getEndDate()->format('Y-m-d'));

        $statement->execute();
        $games = $statement->fetchAll();

        // now hydrate
        $hydratedGames = [];

        foreach ($games as $game) {
            $hydratedGames[] = $this->findOneById($game['id']);
        }

        return $hydratedGames;
    }
}
