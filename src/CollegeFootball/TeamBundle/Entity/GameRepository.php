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
            SELECT distinct(g.id) FROM game g
            JOIN ranking r ON (r.team_id = g.home_team_id OR r.team_id = g.away_team_id)
            WHERE g.date >= :startDate
            AND g.date <= :endDate
            AND r.week_id = :week
            AND r.ap_rank IS NOT NULL
            ORDER BY g.date, STR_TO_DATE(g.time, '%h.%i%p')
        ";

        $em        = $this->getEntityManager();
        $statement = $em->getConnection()->prepare($query);
        $statement->bindValue('startDate', $week->getStartDate()->format('Y-m-d'));
        $statement->bindValue('endDate', $week->getEndDate()->format('Y-m-d'));
        $statement->bindValue('week', $week->getId());

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
