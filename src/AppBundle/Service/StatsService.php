<?php

namespace AppBundle\Service;

use Doctrine\ORM\EntityManager;

use AppBundle\Entity\Game;
use AppBundle\Entity\Team;

class StatsService
{
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function statsForTeam(Team $team)
    {
        $repository = $this->em->getRepository('AppBundle:Game');
        $games      = $repository->createQueryBuilder('g')
            ->where('g.homeTeam = :team OR g.awayTeam = :team')
            ->andWhere('g.winningTeam IS NOT NULL')
            ->setParameter('team', $team)
            ->getQuery()
            ->getResult();

        $defaultStats = [
            'pointsFinal'        => 0,
            'pointsFirst'        => 0,
            'pointsSecond'       => 0,
            'pointsThird'        => 0,
            'pointsFourth'       => 0,
            'ot'                 => 0,
            'totalOffenseYards'  => 0,
            'rushingYards'       => 0,
            'rushingAttempts'    => 0,
            'passingYards'       => 0,
            'passingAttempts'    => 0,
            'passingCompletions' => 0,
            'turnoverCount'      => 0,
            'penaltyYards'       => 0,
            'gameCount'          => count($games),
        ];

        $stats = [
            'team'     => $defaultStats,
            'opponent' => $defaultStats,
        ];

        foreach ($games as $game) {
            $gameStats = $game->getStats();

            if ($game->getHomeTeam() == $team) {
                $teamStats     = $gameStats['homeStats'];
                $opponentStats = $gameStats['awayStats'];
            } else {
                $teamStats     = $gameStats['awayStats'];
                $opponentStats = $gameStats['homeStats'];
            }

            foreach ($stats as $key => $value) {
                $teamOrOpponentStats = $teamStats;

                if ($key == 'opponent') {
                    $teamOrOpponentStats = $opponentStats;
                }

                foreach ($value as $singleStat => $singleStatValue) {
                    if (array_key_exists($singleStat, $teamOrOpponentStats)) {
                        $stats[$key][$singleStat] = $singleStatValue + $teamOrOpponentStats[$singleStat];
                    }
                }
            }
        }

        $gameCount = count($games);

        if ($gameCount) {
            foreach ($stats as $key => $value) {
                foreach ($value as $singleStat => $singleStatValue) {
                    $stats[$key][$singleStat] = round($singleStatValue / $gameCount, 1);
                }
            }
        }

        return $stats;
    }

    /**
     * Compare stats for teams in game
     */
    public function gameComparison(Game $game = null)
    {
        if (! $game) {
            /* off week */
            return;
        }

        $homeTeam = $game->getHomeTeam();
        $awayTeam = $game->getAwayTeam();

        $homeName = $homeTeam->getNameAbbr();
        $awayName = $awayTeam->getNameAbbr();

        /* get stats */
        $homeTeamStats = $this->statsForTeam($homeTeam);
        $awayTeamStats = $this->statsForTeam($awayTeam);

        /* if there are null stats, just return a default */
        if ($homeTeamStats['team']['gameCount'] == 0 || $awayTeamStats['team']['gameCount'] == 0) {
            return [
                'stats'      => null,
                'homeChance' => null,
                'awayChance' => null,
            ];
        }

        $homeScoringMargin = $homeTeamStats['team']['pointsFinal'] - $homeTeamStats['opponent']['pointsFinal'];
        $awayScoringMargin = $awayTeamStats['team']['pointsFinal'] - $awayTeamStats['opponent']['pointsFinal'];

        $homeTurnovers         = $homeTeamStats['team']['turnoverCount'];
        $awayTurnovers         = $awayTeamStats['team']['turnoverCount'];
        $homeOpponentTurnovers = $homeTeamStats['opponent']['turnoverCount'];
        $awayOpponentTurnovers = $awayTeamStats['opponent']['turnoverCount'];

        if ($homeTeamStats['team']['passingAttempts'] && $awayTeamStats['team']['passingAttempts']) {
            $homePassing         = round(($homeTeamStats['team']['passingCompletions'] / $homeTeamStats['team']['passingAttempts']) * 100, 2);
            $awayPassing         = round(($awayTeamStats['team']['passingCompletions'] / $awayTeamStats['team']['passingAttempts']) * 100, 2);
            $homeOpponentPassing = round(($homeTeamStats['opponent']['passingCompletions'] / $homeTeamStats['opponent']['passingAttempts']) * 100, 2);
            $awayOpponentPassing = round(($awayTeamStats['opponent']['passingCompletions'] / $awayTeamStats['opponent']['passingAttempts']) * 100, 2);

            $homeRushing         = round(($homeTeamStats['team']['rushingYards'] / $homeTeamStats['team']['rushingAttempts']), 2);
            $awayRushing         = round(($awayTeamStats['team']['rushingYards'] / $awayTeamStats['team']['rushingAttempts']), 2);
            $homeOpponentRushing = round(($homeTeamStats['opponent']['rushingYards'] / $homeTeamStats['opponent']['rushingAttempts']), 2);
            $awayOpponentRushing = round(($awayTeamStats['opponent']['rushingYards'] / $awayTeamStats['opponent']['rushingAttempts']), 2);
        } else {
            $homePassing         = 1;
            $awayPassing         = 1;
            $homeOpponentPassing = 1;
            $awayOpponentPassing = 1;

            $homeRushing         = 1;
            $awayRushing         = 1;
            $homeOpponentRushing = 1;
            $awayOpponentRushing = 1;
        }

        $stats = [
            'Scoring Margin' => [
                'home'   => $homeScoringMargin,
                'away'   => $awayScoringMargin,
                'winner' => ($homeScoringMargin > $awayScoringMargin ? $homeName : $awayName),
            ],
            'Total Offense' => [
                'home'   => $homeTeamStats['team']['totalOffenseYards'],
                'away'   => $awayTeamStats['team']['totalOffenseYards'],
                'winner' => ($homeTeamStats['team']['totalOffenseYards'] > $awayTeamStats['team']['totalOffenseYards'] ? $homeName : $awayName),
            ],
            'Opponent Total Offense' => [
                'home'   => $homeTeamStats['opponent']['totalOffenseYards'],
                'away'   => $awayTeamStats['opponent']['totalOffenseYards'],
                'winner' => ($homeTeamStats['opponent']['totalOffenseYards'] < $awayTeamStats['opponent']['totalOffenseYards'] ? $homeName : $awayName),
            ],
            'Turnovers' => [
                'home'   => $homeOpponentTurnovers,
                'away'   => $awayOpponentTurnovers,
                'winner' => ($homeOpponentTurnovers < $awayOpponentTurnovers ? $homeName : $awayName),
            ],
            'Opponent Turnovers' => [
                'home'   => $homeTurnovers,
                'away'   => $awayTurnovers,
                'winner' => ($homeTurnovers > $awayTurnovers ? $homeName : $awayName),
            ],
            'Pass %' => [
                'home'   => $homePassing,
                'away'   => $awayPassing,
                'winner' => ($homePassing > $awayPassing ? $homeName : $awayName),
            ],
            'Opponent Pass %' => [
                'home'   => $homeOpponentPassing,
                'away'   => $awayOpponentPassing,
                'winner' => ($homeOpponentPassing < $awayOpponentPassing ? $homeName : $awayName),
            ],
            'Rushing Yds/Carry' => [
                'home'   => $homeRushing,
                'away'   => $awayRushing,
                'winner' => ($homeRushing > $awayRushing ? $homeName : $awayName),
            ],
            'Opponent Rushing Yds/Carry' => [
                'home'   => $homeOpponentRushing,
                'away'   => $awayOpponentRushing,
                'winner' => ($homeOpponentRushing < $awayOpponentRushing ? $homeName : $awayName),
            ],
            'Penalty Yds' => [
                'home'   => $homeTeamStats['team']['penaltyYards'],
                'away'   => $awayTeamStats['team']['penaltyYards'],
                'winner' => ($homeTeamStats['team']['penaltyYards'] < round($awayTeamStats['team']['penaltyYards'], 2) ? $homeName : $awayName),
            ],
            'Opponent Penalty Yds' => [
                'home'   => $homeTeamStats['opponent']['penaltyYards'],
                'away'   => $awayTeamStats['opponent']['penaltyYards'],
                'winner' => ($homeTeamStats['opponent']['penaltyYards'] > round($awayTeamStats['opponent']['penaltyYards'], 2) ? $homeName : $awayName),
            ],
        ];

        $calculatedWinner = $this->calculateWinner($stats, $homeTeamStats, $awayTeamStats);

        return [
            'stats'      => $stats,
            'homeChance' => $calculatedWinner['home'],
            'awayChance' => $calculatedWinner['away'],
        ];
    }

    /**
     * Find all calculated winners for a set of games
     */
    public function gameWinners(array $games)
    {
        $calculatedWinners = [];

        foreach ($games as $game) {
            $calculatedWinners[$game['id']] = $this->gameComparison($game);
        }

        return $calculatedWinners;
    }

    /**
     * Try to calculate the winner
     */
    private function calculateWinner($stats, $homeTeamStats, $awayTeamStats)
    {
        $winningChance = [
            'home' => 0,
            'away' => 0,
        ];

        $statCount = 0;

        /**
         * Pythagorean Projection
         *
         * Pythagorean wins = ((Points For ^ 2.37) / (Points For ^ 2.37 + Points Against ^ 2.37)) * # of games
         *
         * NOTE: This is just the best way found so far. By no means a good estimate. Below "Points Againts" has been changed to the other teams points.
         */
        $exp = 2.37;

        /* points */
        $winningChance['home'] = pow($homeTeamStats['team']['pointsFinal'], $exp) / (pow($homeTeamStats['team']['pointsFinal'], $exp) + pow($awayTeamStats['team']['pointsFinal'], $exp)) * $homeTeamStats['team']['gameCount'];
        $winningChance['away'] = pow($awayTeamStats['team']['pointsFinal'], $exp) / (pow($awayTeamStats['team']['pointsFinal'], $exp) + pow($homeTeamStats['team']['pointsFinal'], $exp)) * $awayTeamStats['team']['gameCount'];
        $statCount++;

        /* offense */
        $winningChance['home'] += pow($homeTeamStats['team']['totalOffenseYards'], $exp) / (pow($homeTeamStats['team']['totalOffenseYards'], $exp) + pow($awayTeamStats['opponent']['totalOffenseYards'], $exp)) * $homeTeamStats['team']['gameCount'];
        $winningChance['away'] += pow($awayTeamStats['team']['totalOffenseYards'], $exp) / (pow($awayTeamStats['team']['totalOffenseYards'], $exp) + pow($homeTeamStats['opponent']['totalOffenseYards'], $exp)) * $awayTeamStats['team']['gameCount'];
        $statCount++;

        /* opponent offense */
        $winningChance['home'] += pow($homeTeamStats['opponent']['totalOffenseYards'], $exp) / (pow($homeTeamStats['opponent']['totalOffenseYards'], $exp) + pow($awayTeamStats['team']['totalOffenseYards'], $exp)) * $homeTeamStats['opponent']['gameCount'];
        $winningChance['away'] += pow($awayTeamStats['opponent']['totalOffenseYards'], $exp) / (pow($awayTeamStats['opponent']['totalOffenseYards'], $exp) + pow($homeTeamStats['team']['totalOffenseYards'], $exp)) * $awayTeamStats['opponent']['gameCount'];
        $statCount++;

        /* opponent offense */
        if ($stats['Scoring Margin']['home'] > 0 && $stats['Scoring Margin']['away'] > 0) {
            $winningChance['home'] += pow($stats['Scoring Margin']['home'], $exp) / (pow($stats['Scoring Margin']['home'], $exp) + pow($stats['Scoring Margin']['away'], $exp)) * $homeTeamStats['team']['gameCount'];
            $winningChance['away'] += pow($stats['Scoring Margin']['away'], $exp) / (pow($stats['Scoring Margin']['away'], $exp) + pow($stats['Scoring Margin']['home'], $exp)) * $awayTeamStats['team']['gameCount'];
            $statCount++;
        } else {
            if ($stats['Scoring Margin']['home'] < 0) {
                $winningChance['away'] += 1;
                $statCount++;
            }
            if ($stats['Scoring Margin']['away'] < 0) {
                $winningChance['home'] += 1;
                $statCount++;
            }
        }

        if ($winningChance['home'] > $winningChance['away']) {
            $homeChance = $winningChance['home'] / $statCount;
            $awayChance = 1 - $homeChance;
        } else {
            $awayChance = $winningChance['away'] / $statCount;
            $homeChance = 1 - $awayChance;
        }

        $winningChance['home'] = round($homeChance * 100, 2);
        $winningChance['away'] = round($awayChance * 100, 2);

        return $winningChance;
    }
}
