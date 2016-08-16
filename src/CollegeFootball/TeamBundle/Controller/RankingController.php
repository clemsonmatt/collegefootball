<?php

namespace CollegeFootball\TeamBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

use CollegeFootball\AppBundle\Entity\Week;
use CollegeFootball\TeamBundle\Form\Type\RankingsType;

/**
 * @Route("ranking")
 */
class RankingController extends Controller
{
    /**
     * @Route("/{week}", name="collegefootball_team_ranking_index", defaults={"week": null})
     */
    public function indexAction(Week $week = null)
    {
        $em         = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('CollegeFootballAppBundle:Week');

        if (! $week) {
            $weeks = $repository->findBy(['season' => date('Y')], ['endDate' => 'ASC']);
            $week  = $weeks[0];
        } else {
            $weeks = $repository->findBy(['season' => $week->getSeason()], ['endDate' => 'ASC']);
        }

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
            'weeks'                 => $weeks,
            'week'                  => $week,
        ]);
    }

    /**
     * @Route("/{week}/add", name="collegefootball_team_ranking_add")
     */
    public function addAction(Week $week, Request $request)
    {
        $form = $this->createForm(RankingsType::class);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            foreach ($form['rankings']->getData() as $ranking) {
                $ranking->setWeek($week);
                $em->persist($ranking);
            }

            $em->flush();
            return $this->redirectToRoute('collegefootball_team_ranking_index', [
                'week' => $week->getId(),
            ]);
        }

        return $this->render('CollegeFootballTeamBundle:Ranking:addEdit.html.twig', [
            'form' => $form->createView(),
            'week' => $week,
        ]);
    }
}
