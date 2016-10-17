<?php

namespace CollegeFootball\TeamBundle\Service;

use Doctrine\ORM\EntityManager;
use JMS\DiExtraBundle\Annotation as DI;

use CollegeFootball\TeamBundle\Entity\Game;
use CollegeFootball\TeamBundle\Entity\Team;

/**
* @DI\Service("collegefootball.team.stats")
*/
class StatsService
{
    private $em;

    /**
     * @DI\InjectParams({
     *      "em" = @DI\Inject("doctrine.orm.entity_manager")
     *  })
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function statsForTeam(Team $team)
    {
        $repository = $this->em->getRepository('CollegeFootballTeamBundle:Game');
        $games      = $repository->createQueryBuilder('g')
            ->where('g.homeTeam = :team OR g.awayTeam = :team')
            ->andWhere('g.winningTeam IS NOT NULL')
            ->setParameter('team', $team)
            ->getQuery()
            ->getResult();

        $defaultStats = [
            'pointsFinal'           => 0,
            'pointsFirst'           => 0,
            'pointsSecond'          => 0,
            'pointsThird'           => 0,
            'pointsFourth'          => 0,
            'ot1'                   => 0,
            'ot2'                   => 0,
            'ot3'                   => 0,
            'ot4'                   => 0,
            'totalOffenseYards'     => 0,
            'rushingYards'          => 0,
            'rushingAttempts'       => 0,
            'rushingTd'             => 0,
            'passingYards'          => 0,
            'passingAttempts'       => 0,
            'passingCompletions'    => 0,
            'passingTd'             => 0,
            'passingInterceptions'  => 0,
            'thirdDownAttempts'     => 0,
            'thirdDownConversions'  => 0,
            'fourthDownAttempts'    => 0,
            'fourthDownConversions' => 0,
            'puntReturnCount'       => 0,
            'puntReturnYards'       => 0,
            'puntReturnTd'          => 0,
            'puntCount'             => 0,
            'puntYards'             => 0,
            'fieldGoalAttempts'     => 0,
            'fieldGoalMade'         => 0,
            'patAttempts'           => 0,
            'patMade'               => 0,
            'interceptionCount'     => 0,
            'interceptionYards'     => 0,
            'interceptionTd'        => 0,
            'fumbleCount'           => 0,
            'penaltyCount'          => 0,
            'penaltyYards'          => 0,
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

        foreach ($stats as $key => $value) {
            foreach ($value as $singleStat => $singleStatValue) {
                $stats[$key][$singleStat] = round($singleStatValue / $gameCount, 1);
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

        $homeScoringMargin = $homeTeamStats['team']['pointsFinal'] - $homeTeamStats['opponent']['pointsFinal'];
        $awayScoringMargin = $awayTeamStats['team']['pointsFinal'] - $awayTeamStats['opponent']['pointsFinal'];

        $homeTurnovers         = $homeTeamStats['team']['interceptionCount'] + $homeTeamStats['team']['fumbleCount'];
        $awayTurnovers         = $awayTeamStats['team']['interceptionCount'] + $awayTeamStats['team']['fumbleCount'];
        $homeOpponentTurnovers = $homeTeamStats['opponent']['interceptionCount'] + $homeTeamStats['opponent']['fumbleCount'];
        $awayOpponentTurnovers = $awayTeamStats['opponent']['interceptionCount'] + $awayTeamStats['opponent']['fumbleCount'];

        $homePassing         = round(($homeTeamStats['team']['passingCompletions'] / $homeTeamStats['team']['passingAttempts']) * 100, 2);
        $awayPassing         = round(($awayTeamStats['team']['passingCompletions'] / $awayTeamStats['team']['passingAttempts']) * 100, 2);
        $homeOpponentPassing = round(($homeTeamStats['opponent']['passingCompletions'] / $homeTeamStats['opponent']['passingAttempts']) * 100, 2);
        $awayOpponentPassing = round(($awayTeamStats['opponent']['passingCompletions'] / $awayTeamStats['opponent']['passingAttempts']) * 100, 2);

        $homeRushing         = round(($homeTeamStats['team']['rushingYards'] / $homeTeamStats['team']['rushingAttempts']), 2);
        $awayRushing         = round(($awayTeamStats['team']['rushingYards'] / $awayTeamStats['team']['rushingAttempts']), 2);
        $homeOpponentRushing = round(($homeTeamStats['opponent']['rushingYards'] / $homeTeamStats['opponent']['rushingAttempts']), 2);
        $awayOpponentRushing = round(($awayTeamStats['opponent']['rushingYards'] / $awayTeamStats['opponent']['rushingAttempts']), 2);

        $stats = [
            'Scoring Margin' => [
                'home'   => ($homeScoringMargin > 0 ? '+'.$homeScoringMargin : $homeScoringMargin),
                'away'   => ($awayScoringMargin > 0 ? '+'.$awayScoringMargin : $awayScoringMargin),
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
     * Try to calculate the winner
     */
    private function calculateWinner($stats, $homeTeamStats, $awayTeamStats)
    {
        $winningChance = [
            'home' => 0,
            'away' => 0,
        ];

        /* scoring margin */
        $winningChance['home'] += round($stats['Scoring Margin']['home'] / $stats['Scoring Margin']['away'], 2);
        $winningChance['away'] += round($stats['Scoring Margin']['away'] / $stats['Scoring Margin']['home'], 2);

        /* total yards */
        $winningChance['home'] += round($homeTeamStats['team']['totalOffenseYards'] / $awayTeamStats['team']['totalOffenseYards'], 2);
        $winningChance['away'] += round($awayTeamStats['team']['totalOffenseYards'] / $homeTeamStats['team']['totalOffenseYards'], 2);

        /* total opponent yards */
        $winningChance['home'] -= round($homeTeamStats['opponent']['totalOffenseYards'] / $awayTeamStats['opponent']['totalOffenseYards'], 2);
        $winningChance['away'] -= round($awayTeamStats['opponent']['totalOffenseYards'] / $homeTeamStats['opponent']['totalOffenseYards'], 2);

        /* passing yards */
        $winningChance['home'] += round(($homeTeamStats['team']['passingYards'] - $awayTeamStats['opponent']['passingYards']) / ($awayTeamStats['team']['passingYards'] - $homeTeamStats['opponent']['passingYards']), 2);
        $winningChance['away'] += round(($awayTeamStats['team']['passingYards'] - $homeTeamStats['opponent']['passingYards']) / ($homeTeamStats['team']['passingYards'] - $awayTeamStats['opponent']['passingYards']), 2);

        /* rushing yards */
        $winningChance['home'] += round(($homeTeamStats['team']['rushingYards'] - $awayTeamStats['opponent']['rushingYards']) / ($awayTeamStats['team']['rushingYards'] - $homeTeamStats['opponent']['rushingYards']), 2);
        $winningChance['away'] += round(($awayTeamStats['team']['rushingYards'] - $homeTeamStats['opponent']['rushingYards']) / ($homeTeamStats['team']['rushingYards'] - $awayTeamStats['opponent']['rushingYards']), 2);

        if ($winningChance['home'] > $winningChance['away']) {
            $awayChance = round(($winningChance['away'] / $winningChance['home']) * 100, 2);
            $homeChance = 100 - $awayChance;
        } else {
            $homeChance = round(($winningChance['home'] / $winningChance['away']) * 100, 2);
            $awayChance = 100 - $homeChance;
        }

        $winningChance['home'] = $homeChance;
        $winningChance['away'] = $awayChance;

        return $winningChance;
    }
}
