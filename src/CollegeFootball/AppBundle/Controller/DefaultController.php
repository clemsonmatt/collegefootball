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
        $games      = $repository->findGamesByWeek($week);

        /* rankings */
        $repository = $em->getRepository('CollegeFootballTeamBundle:Ranking');
        $apRankings = $repository->createQueryBuilder('r')
            ->where('r.apRank IS NOT NULL')
            ->andWhere('r.week = :week')
            ->orderBy('r.apRank')
            ->setParameter('week', $week)
            ->getQuery()
            ->getResult();

        $playoffRankings = $repository->createQueryBuilder('r')
            ->where('r.playoffRank IS NOT NULL')
            ->andWhere('r.week = :week')
            ->orderBy('r.playoffRank')
            ->setParameter('week', $week)
            ->getQuery()
            ->getResult();

        $news = $this->get('collegefootball.app.news')->getNews();

        return $this->render('CollegeFootballAppBundle:Default:index.html.twig', [
            'games'            => $games,
            'ap_rankings'      => $apRankings,
            'playoff_rankings' => $playoffRankings,
            'news'             => $news,
        ]);
    }
}
