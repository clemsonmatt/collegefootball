<?php

namespace CollegeFootball\TeamBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
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
        $season = null;
        if ($week) {
            $season = $week->getSeason();
            $week   = $week->getNumber();
        } else {
            $week = null;
        }

        $weekService = $this->get('collegefootball.team.week');
        $weekResult  = $weekService->currentWeek($season, $week, true);
        $week        = $weekResult['week'];
        $weeks       = $weekResult['seasonWeeks'];

        $em         = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('CollegeFootballTeamBundle:Ranking');
        $apRankings = $repository->createQueryBuilder('r')
            ->where('r.apRank IS NOT NULL')
            ->andWhere('r.week = :week')
            ->orderBy('r.apRank')
            ->setParameter('week', $week)
            ->getQuery()
            ->getResult();

        $coachesPollRankings = $repository->createQueryBuilder('r')
            ->where('r.coachesPollRank IS NOT NULL')
            ->andWhere('r.week = :week')
            ->orderBy('r.coachesPollRank')
            ->setParameter('week', $week)
            ->getQuery()
            ->getResult();

        return $this->render('CollegeFootballTeamBundle:Ranking:index.html.twig', [
            'ap_rankings'           => $apRankings,
            'coaches_poll_rankings' => $coachesPollRankings,
            'weeks'                 => $weeks,
            'week'                  => $week,
        ]);
    }

    /**
     * @Route("/{week}/add", name="collegefootball_team_ranking_add")
     * @Security("is_granted('ROLE_MANAGE')")
     */
    public function addAction(Week $week, Request $request)
    {
        $form = $this->createForm(RankingsType::class);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            foreach ($form['rankings']->getData() as $ranking) {
                if ($ranking->getTeam()) {
                    $ranking->setWeek($week);
                    $em->persist($ranking);
                }
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
