<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use AppBundle\Service\NewsService;
use AppBundle\Service\WeekService;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="app_index")
     */
    public function indexAction(NewsService $newsService, WeekService $weekService)
    {
        $weekResult = $weekService->currentWeek();
        $week       = $weekResult['week'];

        $em         = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('AppBundle:Game');
        $games      = $repository->findGamesByWeek($week, true);

        /* rankings */
        $repository = $em->getRepository('AppBundle:Ranking');
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

        $news = $newsService->getNews();

        return $this->render('AppBundle:Default:index.html.twig', [
            'games'            => $games,
            'ap_rankings'      => $apRankings,
            'playoff_rankings' => $playoffRankings,
            'news'             => $news,
        ]);
    }
}
