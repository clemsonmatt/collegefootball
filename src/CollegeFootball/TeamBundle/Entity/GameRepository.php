<?php

namespace CollegeFootball\TeamBundle\Entity;

use Doctrine\ORM\EntityRepository;

use CollegeFootball\AppBundle\Entity\Week;
use CollegeFootball\TeamBundle\Entity\Team;

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

    public function findGamesByWeek(Week $week, $top25Only = false)
    {
        if ($top25Only) {
            $query  = "
                SELECT distinct(g.id) FROM game g
                JOIN ranking r ON (r.team_id = g.home_team_id OR r.team_id = g.away_team_id)
                WHERE g.date >= :startDate
                AND g.date <= :endDate
                AND r.week_id = :week
                AND r.ap_rank IS NOT NULL
                ORDER BY g.date, STR_TO_DATE(g.time, '%h.%i%p')
            ";
        } else {
            $query  = "
                SELECT g.*,
                    homeTeam.id as homeTeamId,
                    homeTeam.slug as homeTeamSlug,
                    homeTeam.logo as homeTeamLogo,
                    homeTeam.name_short as homeTeamNameShort,
                    awayTeam.id as awayTeamId,
                    awayTeam.slug as awayTeamSlug,
                    awayTeam.logo as awayTeamLogo,
                    awayTeam.name_short as awayTeamNameShort,
                    winningTeam.id as winningTeamId,
                    winningTeam.slug as winningTeamSlug,
                    winningTeam.logo as winningTeamLogo,
                    winningTeam.name_short as winningTeamNameShort
                FROM game g
                JOIN team homeTeam ON g.home_team_id = homeTeam.id
                JOIN team awayTeam ON g.away_team_id = awayTeam.id
                LEFT JOIN team winningTeam ON g.winning_team_id = winningTeam.id
                WHERE g.date >= :startDate
                AND g.date <= :endDate
                ORDER BY g.date, STR_TO_DATE(g.time, '%h.%i%p')
            ";
        }

        $em        = $this->getEntityManager();
        $statement = $em->getConnection()->prepare($query);
        $statement->bindValue('startDate', $week->getStartDate()->format('Y-m-d'));
        $statement->bindValue('endDate', $week->getEndDate()->format('Y-m-d'));

        if ($top25Only) {
            $statement->bindValue('week', $week->getId());
        }

        $statement->execute();
        $games = $statement->fetchAll();

        // format the results
        $formattedGames = [];

        $imagePrefixPath = Team::imagePrefixPath();

        foreach ($games as $game) {
            $formattedGames[] = [
                'id'              => $game['id'],
                'date'            => $game['date'],
                'time'            => $game['time'],
                'location'        => $game['location'],
                'spread'          => $game['spread'],
                'predictedWinner' => $game['predicted_winner'],
                'canPick'         => $this->canPick($game),
                'homeTeam'        => [
                    'id'               => $game['homeTeamId'],
                    'slug'             => $game['homeTeamSlug'],
                    'imageLocation'    => $imagePrefixPath.$game['homeTeamLogo'],
                    'rankingNameShort' => $game['homeTeamNameShort'],
                ],
                'awayTeam' => [
                    'id'               => $game['awayTeamId'],
                    'slug'             => $game['awayTeamSlug'],
                    'imageLocation'    => $imagePrefixPath.$game['awayTeamLogo'],
                    'rankingNameShort' => $game['awayTeamNameShort'],
                ],
                'winningTeam' => [
                    'id'               => $game['winningTeamId'],
                    'slug'             => $game['winningTeamSlug'],
                    'imageLocation'    => $imagePrefixPath.$game['winningTeamLogo'],
                    'rankingNameShort' => $game['winningTeamNameShort'],
                ],
            ];
        }

        return $formattedGames;

        // now hydrate
        // $hydratedGames = [];

        // foreach ($games as $game) {
        //     $hydratedGames[] = $this->findOneById($game['id']);
        // }

        // return $hydratedGames;
    }

    private function canPick($game)
    {
        if ($game['winningTeamId']) {
            return false;
        }

        $now  = new \DateTime();
        $date = $game['date'];
        $time = new \DateTime($game['time']);

        if ($date < $now->format('Y-m-d') || ($date == $now->modify('-3 hour')->format('Y-m-d') && $time->format('U') < $now->modify('-1 hour')->format('U'))) {
            return false;
        }

        return true;
    }
}
