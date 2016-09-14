<?php

namespace CollegeFootball\TeamBundle\Service;

use Doctrine\ORM\EntityManager;
use JMS\DiExtraBundle\Annotation as DI;

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
                    $stats[$key][$singleStat] = $singleStatValue + $teamOrOpponentStats[$singleStat];
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
}
