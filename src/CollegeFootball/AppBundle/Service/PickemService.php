<?php

namespace CollegeFootball\AppBundle\Service;

use Doctrine\ORM\EntityManager;
use JMS\DiExtraBundle\Annotation as DI;

use CollegeFootball\AppBundle\Entity\Person;
use CollegeFootball\AppBundle\Entity\Week;
use CollegeFootball\TeamBundle\Entity\Game;

/**
* @DI\Service("collegefootball.app.pickem")
*/
class PickemService
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

    public function picksByWeek(Week $week, Game $game = null)
    {
        $repository        = $this->em->getRepository('CollegeFootballAppBundle:Prediction');
        $predictionsByWeek = $repository->createQueryBuilder('p')
            ->join('p.game', 'g')
            ->where('g.date >= :startDate')
            ->andWhere('g.date <= :endDate')
            ->setParameter('startDate', $week->getStartDate())
            ->setParameter('endDate', $week->getEndDate());

        if ($game) {
            $predictionsByWeek = $predictionsByWeek->andWhere('g = :game')
                ->setParameter('game', $game);
        }

        $predictionsByWeek = $predictionsByWeek->getQuery()
            ->getResult();

        $gamePredictions = [];

        foreach ($predictionsByWeek as $prediction) {
            $gameId = $prediction->getGame()->getId();

            $homeAway = 'home';
            if ($prediction->getTeam() == $prediction->getGame()->getAwayTeam()) {
                $homeAway = 'away';
            }

            if (array_key_exists($gameId, $gamePredictions)) {
                if ($homeAway == 'home') {
                    $gamePredictions[$gameId]['homeCount']++;
                } else {
                    $gamePredictions[$gameId]['awayCount']++;
                }
            } else {
                $gamePredictions[$gameId] = [
                    'homeCount' => 0,
                    'awayCount' => 0,
                ];

                if ($homeAway == 'home') {
                    $gamePredictions[$gameId]['homeCount']++;
                } else {
                    $gamePredictions[$gameId]['awayCount']++;
                }
            }
        }

        return $gamePredictions;
    }

    public function leaderboardRank(Person $user)
    {
        /* find all people */
        $repository = $this->em->getRepository('CollegeFootballAppBundle:Person');
        $people     = $repository->createQueryBuilder('p')
            ->where('p.email IS NOT NULL')
            ->getQuery()
            ->getResult();

        /* get score and username */
        $peopleByRank = [];

        foreach ($people as $person) {
            $score = ($person->getPredictionWins() / ($person->getPredictionWins() + $person->getPredictionLosses())) * 100;
            $score = round($score, 1);

            $sortingScores[] = $score;

            $peopleByRank[] = [
                'score'    => $score,
                'username' => $person->getUsername()
            ];
        }

        /* sort the scores */
        rsort($sortingScores);

        /* sort the people by the sorted scores */
        $sortedPeopleByRank = [];

        foreach ($sortingScores as $score) {
            foreach ($peopleByRank as $ranking) {
                if ($score == $ranking['score']) {
                    $sortedPeopleByRank[] = $ranking;
                }
            }
        }

        /* get the current user rank */
        $currentRank = null;

        foreach ($sortedPeopleByRank as $rank => $sortedPerson) {
            if ($sortedPerson['username'] == $user->getUsername()) {
                $currentRank = $rank + 1;
                break;
            }
        }


        return [
            'peopleByRank' => $sortedPeopleByRank,
            'currentRank'  => $currentRank,
        ];
    }
}
