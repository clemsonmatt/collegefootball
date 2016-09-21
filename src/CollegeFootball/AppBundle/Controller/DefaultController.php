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
        $weekResult = $this->get('collegefootball.team.week')->currentWeek();
        $week       = $weekResult['week'];

        $em         = $this->getDoctrine()->getManager();
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
        $repository = $em->getRepository('CollegeFootballTeamBundle:Ranking');
        $apRankings = $repository->createQueryBuilder('r')
            ->where('r.apRank IS NOT NULL')
            ->andWhere('r.week = :week')
            ->orderBy('r.apRank')
            ->setParameter('week', $week)
            ->getQuery()
            ->getResult();

        return $this->render('CollegeFootballAppBundle:Default:index.html.twig', [
            'games'       => $topGames,
            'ap_rankings' => $apRankings,
        ]);
    }
}
