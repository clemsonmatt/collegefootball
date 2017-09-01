<?php

namespace AppBundle\Service;

use Doctrine\ORM\EntityManager;
use JMS\DiExtraBundle\Annotation as DI;

use AppBundle\Entity\Person;
use AppBundle\Entity\Week;
use AppBundle\Entity\Game;

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
        $repository        = $this->em->getRepository('AppBundle:Prediction');
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

            if ($homeAway == 'home') {
                $gamePredictions[$gameId]['people']['home'][] = $prediction->getPerson()->getUsername();
            } else {
                $gamePredictions[$gameId]['people']['away'][] = $prediction->getPerson()->getUsername();
            }
        }

        return $gamePredictions;
    }

    public function predictedWeekWinnersByPerson(Person $person, Week $week)
    {
        $repository           = $this->em->getRepository('AppBundle:Prediction');
        $predictedWeekWinners = $repository->createQueryBuilder('p')
            ->select('t.slug, g.id')
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
            $weekWinners[$weekWinner['id']] = $weekWinner['slug'];
        }

        return $weekWinners;
    }

    public function gamedayWeekPicks(Week $week, Game $game = null)
    {
        if (! $game) {
            $repository = $this->em->getRepository('AppBundle:Game');
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

        $repository = $this->em->getRepository('AppBundle:Person');
        $people     = $repository->findByUsername(['desmondhoward', 'leecorso', 'kirkherbstreit', 'davidpollack', 'thebear']);

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
        $repository = $this->em->getRepository('AppBundle:Person');
        $people     = $repository->createQueryBuilder('p')
            ->where('p.email IS NOT NULL')
            ->getQuery()
            ->getResult();

        /* get score and username */
        $peopleByRank = [];

        foreach ($people as $person) {
            $predictionWins   = $person->getPredictionWins();
            $predictionLosses = $person->getPredictionLosses();

            $percentage = 0;
            $score      = 0;

            if ($predictionWins || $predictionLosses) {
                // find percentage correct
                $rawPercentage = $predictionWins / ($predictionWins + $predictionLosses);
                $percentage    = $rawPercentage * 100;
                $percentage    = round($percentage, 1);

                // find calculated score
                $score = 5 * round($predictionWins * $rawPercentage / 5, 2) * 10;
            }

            $sortingScores[] = $score;

            $peopleByRank[] = [
                'score'      => $score,
                'percentage' => $percentage,
                'username'   => $person->getUsername(),
                'wins'       => $predictionWins,
                'losses'     => $predictionLosses,
                'rank'       => 1,
            ];
        }

        /* sort the scores */
        rsort($sortingScores);

        /* sort the people by the sorted scores */
        $sortedPeopleByRank = [];
        $usedPeople         = [];
        $previousScore      = 0;
        $rank               = 0;

        foreach ($sortingScores as $score) {
            foreach ($peopleByRank as $ranking) {
                if ($score == $ranking['score'] && ! in_array($ranking['username'], $usedPeople)) {
                    if ($score != $previousScore || $rank == 0) {
                        $rank++;
                        $previousScore = $score;
                    }

                    $ranking['rank'] = $rank;

                    $sortedPeopleByRank[] = $ranking;
                    $usedPeople[]         = $ranking['username'];
                }
            }
        }

        /* get the current user rank */
        $currentRank  = 1;
        $currentScore = 0;
        $highestScore = [
            'username' => '--',
            'score'    => 0,
        ];

        foreach ($sortedPeopleByRank as $sortedPerson) {
            $score = $sortedPerson['score'];

            if ($score > $highestScore['score']) {
                $highestScore = [
                    'username' => $user->getUsername(),
                    'score'    => $score
                ];
            }

            if ($sortedPerson['username'] == $user->getUsername() && $highestScore['score'] > 0) {
                $currentRank  = $sortedPerson['rank'];
                $currentScore = $score;
            }
        }


        return [
            'peopleByRank' => $sortedPeopleByRank,
            'currentRank'  => $currentRank,
            'currentScore' => $currentScore,
            'highestScore' => $highestScore,
        ];
    }
}
