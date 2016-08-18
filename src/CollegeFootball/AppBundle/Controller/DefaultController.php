<?php

namespace CollegeFootball\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="collegefootball_app_index")
     */
    public function indexAction()
    {
        $season = date('Y');
        $today  = new \DateTime("now");

        $em          = $this->getDoctrine()->getManager();
        $repository  = $em->getRepository('CollegeFootballAppBundle:Week');
        $seasonWeeks = $repository->createQueryBuilder('w')
            ->where('w.season = :season')
            ->andWhere('w.number > 0')
            ->andWhere('w.endDate >= :today')
            ->orderBy('w.endDate', 'ASC')
            ->setParameter('season', $season)
            ->setParameter('today', $today)
            ->getQuery()
            ->getResult();

        $week = $seasonWeeks[0];

        $repository = $em->getRepository('CollegeFootballTeamBundle:Game');
        $games      = $repository->createQueryBuilder('g')
            ->where('g.date >= :startDate')
            ->andWhere('g.date <= :endDate')
            ->orderBy('g.date, g.time')
            ->setParameter('startDate', $week->getStartDate())
            ->setParameter('endDate', $week->getEndDate())
            ->getQuery()
            ->getResult();

        $topGames = [];

        foreach ($games as $game) {
            if ($game->getHomeTeam()->currentRanking() || $game->getAwayTeam()->currentRanking()) {
                $topGames[] = $game;
            }
        }

        /* rankings */
        $repository = $em->getRepository('CollegeFootballAppBundle:Week');
        $weeks = $repository->createQueryBuilder('w')
            ->where('w.season = :season')
            ->andWhere('w.endDate < :currentWeek')
            ->orderBy('w.endDate', 'ASC')
            ->setParameter('season', date('Y'))
            ->setParameter('currentWeek', $week->getStartDate())
            ->getQuery()
            ->getResult();

        $week = $weeks[0];

        $repository = $em->getRepository('CollegeFootballTeamBundle:Ranking');
        $apRankings = $repository->findBy([
            'week' => $week,
        ], [
            'apRank' => 'ASC',
        ]);

        $coachesPollRankings = $repository->findBy([
            'week' => $week,
        ], [
            'coachesPollRank' => 'ASC',
        ]);

        return $this->render('CollegeFootballAppBundle:Default:index.html.twig', [
            'games'            => $topGames,
            'ap_rankings'      => $apRankings,
            'coaches_rankings' => $coachesPollRankings,
        ]);
    }
}
