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
        $query  = "
            SELECT distinct g.*,
                homeTeam.id as homeTeamId,
                homeTeam.slug as homeTeamSlug,
                homeTeam.logo as homeTeamLogo,
                homeTeam.name as homeTeamName,
                homeTeam.name_short as homeTeamNameShort,
                homeTeam.primary_color as homeTeamPrimaryColor,
                awayTeam.id as awayTeamId,
                awayTeam.slug as awayTeamSlug,
                awayTeam.logo as awayTeamLogo,
                awayTeam.name as awayTeamName,
                awayTeam.name_short as awayTeamNameShort,
                awayTeam.primary_color as awayTeamPrimaryColor,
                winningTeam.id as winningTeamId,
                winningTeam.slug as winningTeamSlug,
                winningTeam.logo as winningTeamLogo,
                winningTeam.name_short as winningTeamNameShort
            FROM game g
            JOIN ranking r ON (r.team_id = g.home_team_id OR r.team_id = g.away_team_id)
            JOIN team homeTeam ON g.home_team_id = homeTeam.id
            JOIN team awayTeam ON g.away_team_id = awayTeam.id
            LEFT JOIN team winningTeam ON g.winning_team_id = winningTeam.id
            WHERE g.date >= :startDate
            AND g.date <= :endDate
        ";

        if ($top25Only) {
            $query .= " AND r.week_id = :week AND r.ap_rank IS NOT NULL";
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
            $dateTime = new \DateTime($game['date'].' '.$game['time']);

            $formattedGames[$dateTime->format('U').uniqid()] = [
                'id'                     => $game['id'],
                'date'                   => $game['date'],
                'time'                   => $game['time'],
                'location'               => $game['location'],
                'stats'                  => $game['stats'],
                'spread'                 => $game['spread'],
                'predictedWinner'        => $game['predicted_winner'],
                'canPick'                => $this->canPick($game),
                'conferenceChampionship' => $game['conference_championship'],
                'bowlName'               => $game['bowl_name'],
                'homeTeam'               => [
                    'id'               => $game['homeTeamId'],
                    'slug'             => $game['homeTeamSlug'],
                    'imageLocation'    => $imagePrefixPath.$game['homeTeamLogo'],
                    'name'             => $game['homeTeamName'],
                    'rankingNameShort' => $game['homeTeamNameShort'],
                    'primaryColor'     => $game['homeTeamPrimaryColor'],
                ],
                'awayTeam' => [
                    'id'               => $game['awayTeamId'],
                    'slug'             => $game['awayTeamSlug'],
                    'imageLocation'    => $imagePrefixPath.$game['awayTeamLogo'],
                    'name'             => $game['awayTeamName'],
                    'rankingNameShort' => $game['awayTeamNameShort'],
                    'primaryColor'     => $game['awayTeamPrimaryColor'],
                ],
                'winningTeam' => [
                    'id'               => $game['winningTeamId'],
                    'slug'             => $game['winningTeamSlug'],
                    'imageLocation'    => $imagePrefixPath.$game['winningTeamLogo'],
                    'rankingNameShort' => $game['winningTeamNameShort'],
                ],
            ];
        }

        ksort($formattedGames);

        return $formattedGames;
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
