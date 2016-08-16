<?php

namespace CollegeFootball\TeamBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("ranking")
 */
class RankingController extends Controller
{
    /**
     * @Route("/{week}", name="collegefootball_team_ranking_index", defaults={"week" : 1})
     */
    public function indexAction($week = 1)
    {
        $em         = $this->getDoctrine()->getManager();
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

        return $this->render('CollegeFootballTeamBundle:Ranking:index.html.twig', [
            'ap_rankings'           => $apRankings,
            'coaches_poll_rankings' => $coachesPollRankings,
        ]);
    }
}
