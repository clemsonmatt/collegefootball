<?php

namespace AppBundle\Entity;

use Doctrine\ORM\EntityRepository;

use AppBundle\Entity\Week;
use AppBundle\Entity\Team;

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

    public function findGamesByWeek(Week $week, $top25Only = false, $pickemOnly = false, $missingStatsOnly = false)
    {
        $query  = "
            SELECT distinct g.*,
                homeTeam.id as homeTeamId,
                homeTeam.slug as homeTeamSlug,
                homeTeam.logo as homeTeamLogo,
                homeTeam.name as homeTeamName,
                homeTeam.name_short as homeTeamNameShort,
                homeTeam.name_abbr as homeTeamNameAbbr,
                homeTeam.primary_color as homeTeamPrimaryColor,
                homeTeam.conference_id,
                homeTeamRank.ap_rank as homeTeamApRank,
                homeTeamRank.playoff_rank as homeTeamPlayoffRank,
                awayTeam.id as awayTeamId,
                awayTeam.slug as awayTeamSlug,
                awayTeam.logo as awayTeamLogo,
                awayTeam.name as awayTeamName,
                awayTeam.name_short as awayTeamNameShort,
                awayTeam.name_abbr as awayTeamNameAbbr,
                awayTeam.primary_color as awayTeamPrimaryColor,
                awayTeamRank.ap_rank as awayTeamApRank,
                awayTeamRank.playoff_rank as awayTeamPlayoffRank,
                winningTeam.id as winningTeamId,
                winningTeam.slug as winningTeamSlug,
                winningTeam.logo as winningTeamLogo,
                winningTeam.name_short as winningTeamNameShort,
                c.name_short as conference
            FROM game g
            LEFT JOIN team homeTeam ON g.home_team_id = homeTeam.id
            LEFT JOIN team awayTeam ON g.away_team_id = awayTeam.id
            LEFT JOIN conference c ON homeTeam.conference_id = c.id
            LEFT JOIN ranking homeTeamRank ON homeTeamRank.team_id = g.home_team_id AND homeTeamRank.week_id = :week
            LEFT JOIN ranking awayTeamRank ON awayTeamRank.team_id = g.away_team_id AND awayTeamRank.week_id = :week
            LEFT JOIN team winningTeam ON g.winning_team_id = winningTeam.id
        ";

        if ($top25Only) {
            $query .= " JOIN ranking r ON (r.team_id = g.home_team_id OR r.team_id = g.away_team_id)";
        }

        $query .= "
            WHERE g.date >= :startDate
            AND g.date <= :endDate
            AND g.canceled = 0
        ";

        if ($top25Only) {
            $query .= " AND r.week_id = :week AND r.ap_rank IS NOT NULL";
        } elseif ($pickemOnly) {
            $query .= " AND g.pickem_game = 1";
        } elseif ($missingStatsOnly) {
            $query .= " AND g.stats = 'N;' AND STR_TO_DATE(g.time, '%h:%i %p') >= :time";
        }

        $em        = $this->getEntityManager();
        $statement = $em->getConnection()->prepare($query);
        $statement->bindValue('week', $week->getId());
        $statement->bindValue('startDate', $week->getStartDate()->format('Y-m-d'));

        if ($missingStatsOnly) {
            $now = new \DateTime('+3 hours');
            $statement->bindValue('endDate', $now->format('Y-m-d'));
            $statement->bindValue('time', $now->format('h:i:s'));
        } else {
            $statement->bindValue('endDate', $week->getEndDate()->format('Y-m-d'));
        }

        $statement->execute();
        $games = $statement->fetchAll();

        // format the results
        $formattedGames = [];

        $imagePrefixPath = Team::imagePrefixPath();

        foreach ($games as $game) {
            $dateTime          = new \DateTime($game['date'].' '.$game['time']);
            $homeTeamName      = $game['homeTeamName'];
            $homeTeamNameShort = $game['homeTeamNameShort'];
            $awayTeamName      = $game['awayTeamName'];
            $awayTeamNameShort = $game['awayTeamNameShort'];

            if ($game['homeTeamPlayoffRank']) {
                $homeTeamName      = '#'.$game['homeTeamPlayoffRank'].' '.$homeTeamName;
                $homeTeamNameShort = '#'.$game['homeTeamPlayoffRank'].' '.$homeTeamNameShort;
            } elseif ($game['homeTeamApRank']) {
                $homeTeamName      = '#'.$game['homeTeamApRank'].' '.$homeTeamName;
                $homeTeamNameShort = '#'.$game['homeTeamApRank'].' '.$homeTeamNameShort;
            }

            if ($game['awayTeamPlayoffRank']) {
                $awayTeamName      = '#'.$game['awayTeamPlayoffRank'].' '.$awayTeamName;
                $awayTeamNameShort = '#'.$game['awayTeamPlayoffRank'].' '.$awayTeamNameShort;
            } elseif ($game['awayTeamApRank']) {
                $awayTeamName      = '#'.$game['awayTeamApRank'].' '.$awayTeamName;
                $awayTeamNameShort = '#'.$game['awayTeamApRank'].' '.$awayTeamNameShort;
            }

            $formattedGames[$dateTime->format('U').uniqid()] = [
                'id'                     => $game['id'],
                'date'                   => $game['date'],
                'time'                   => $game['time'],
                'location'               => $game['location'],
                'stats'                  => unserialize($game['stats']),
                'spread'                 => $game['spread'],
                'predictedWinner'        => $game['predicted_winner'],
                'canPick'                => $this->canPick($game),
                'conferenceChampionship' => $game['conference_championship'],
                'conference'             => $game['conference'],
                'bowlName'               => $game['bowl_name'],
                'isPickemGame'           => $game['pickem_game'],
                'winningChance'          => unserialize($game['winning_chance']),
                'homeTeam'               => [
                    'id'               => $game['homeTeamId'],
                    'slug'             => $game['homeTeamSlug'],
                    'imageLocation'    => $imagePrefixPath.$game['homeTeamLogo'],
                    'name'             => $homeTeamName,
                    'rankingNameShort' => $homeTeamNameShort,
                    'nameAbbr'         => $game['homeTeamNameAbbr'],
                    'primaryColor'     => $game['homeTeamPrimaryColor'],
                ],
                'awayTeam' => [
                    'id'               => $game['awayTeamId'],
                    'slug'             => $game['awayTeamSlug'],
                    'imageLocation'    => $imagePrefixPath.$game['awayTeamLogo'],
                    'name'             => $awayTeamName,
                    'rankingNameShort' => $awayTeamNameShort,
                    'nameAbbr'         => $game['awayTeamNameAbbr'],
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
        } elseif ($game['homeTeamId'] == "" || $game['awayTeamId'] == "") {
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
