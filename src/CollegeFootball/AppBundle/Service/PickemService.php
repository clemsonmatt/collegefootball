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

    public function predictedWeekWinnersByPerson(Person $person, Week $week)
    {
        $repository           = $this->em->getRepository('CollegeFootballAppBundle:Prediction');
        $predictedWeekWinners = $repository->createQueryBuilder('p')
            ->select('t.slug')
            ->join('p.person', 'person')
            ->join('p.team', 't')
            ->join('p.game', 'g')
            ->where('person.username = :username')
            ->andWhere('g.date >= :startDate')
            ->andWhere('g.date <= :endDate')
            ->orderBy('g.date, g.time')
            ->setParameter('username', $person->getUsername())
            ->setParameter('startDate', $week->getStartDate())
            ->setParameter('endDate', $week->getEndDate())
            ->getQuery()
            ->getResult();

        $weekWinners = [];
        foreach ($predictedWeekWinners as $weekWinner) {
            $weekWinners[] = $weekWinner['slug'];
        }

        return $weekWinners;
    }

    public function gamedayWeekPicks(Week $week, Game $game = null)
    {
        if (! $game) {
            $repository = $this->em->getRepository('CollegeFootballTeamBundle:Game');
            $games      = $repository->createQueryBuilder('g')
                ->where('g.date >= :startDate')
                ->andWhere('g.date <= :endDate')
                ->orderBy('g.date, g.time')
                ->setParameter('startDate', $week->getStartDate())
                ->setParameter('endDate', $week->getEndDate())
                ->getQuery()
                ->getResult();
        } else {
            $games[] = $game;
        }

        $repository = $this->em->getRepository('CollegeFootballAppBundle:Person');
        $people     = $repository->findByUsername(['desmondhoward', 'leecorso', 'kirkherbstreit']);

        foreach ($people as $person) {
            $weekWinners[$person->getUsername()] = $this->predictedWeekWinnersByPerson($person, $week);
        }

        $gamedayPicks = [];

        foreach ($games as $game) {
            foreach ($weekWinners as $username => $predictedWinners) {
                foreach ($predictedWinners as $predictedWinnerSlug) {
                    if ($game->getHomeTeam()->getSlug() == $predictedWinnerSlug || $game->getAwayTeam()->getSlug() == $predictedWinnerSlug) {
                        $gamedayPicks[$username][] = [
                            'game'       => $game,
                            'winnerSlug' => $predictedWinnerSlug,
                        ];
                    }
                }
            }
        }

        return $gamedayPicks;
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
