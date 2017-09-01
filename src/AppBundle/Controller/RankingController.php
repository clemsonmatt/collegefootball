<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use AppBundle\Entity\Week;
use AppBundle\Form\Type\RankingsType;
use AppBundle\Service\WeekService;

/**
 * @Route("/ranking")
 */
class RankingController extends Controller
{
    /**
     * @Route("/{week}", name="app_ranking_index", defaults={"week": null})
     */
    public function indexAction(Week $week = null, WeekService $weekService)
    {
        $season = null;
        if ($week) {
            $season = $week->getSeason();
            $week   = $week->getNumber();
        } else {
            $week = null;
        }

        $weekResult = $weekService->currentWeek($season, $week, true);
        $week       = $weekResult['week'];
        $weeks      = $weekResult['seasonWeeks'];

        $em         = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('AppBundle:Ranking');
        $playoffRankings = $repository->createQueryBuilder('r')
            ->where('r.playoffRank IS NOT NULL')
            ->andWhere('r.week = :week')
            ->orderBy('r.playoffRank')
            ->setParameter('week', $week)
            ->getQuery()
            ->getResult();

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

        return $this->render('AppBundle:Ranking:index.html.twig', [
            'playoff_rankings'      => $playoffRankings,
            'ap_rankings'           => $apRankings,
            'coaches_poll_rankings' => $coachesPollRankings,
            'weeks'                 => $weeks,
            'week'                  => $week,
        ]);
    }

    /**
     * @Route("/{week}/{rankType}/add", name="app_ranking_add", requirements={"rankType": "apRank|coachesPollRank|playoffRank"})
     * @Security("is_granted('ROLE_MANAGE')")
     */
    public function addAction(Week $week, $rankType, Request $request)
    {
        $form = $this->createForm(RankingsType::class, null, [
            'rank_type' => $rankType
        ]);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $em         = $this->getDoctrine()->getManager();
            $repository = $em->getRepository('AppBundle:Ranking');

            foreach ($form['rankings']->getData() as $ranking) {
                $weekRanking = $repository->findOneBy([
                    'team' => $ranking->getTeam(),
                    'week' => $week,
                ]);

                if ($ranking->getTeam()) {
                    if ($weekRanking) {
                        /* update */
                        $rankingSetter = 'set'.ucwords($rankType);
                        $rankingGetter = 'get'.ucwords($rankType);

                        $weekRanking->{$rankingSetter}($ranking->{$rankingGetter}());
                    } else {
                        $ranking->setWeek($week);
                        $em->persist($ranking);
                    }
                }
            }

            $em->flush();
            return $this->redirectToRoute('app_ranking_index', [
                'week' => $week->getId(),
            ]);
        }

        return $this->render('AppBundle:Ranking:addEdit.html.twig', [
            'form'     => $form->createView(),
            'week'     => $week,
            'rankType' => $rankType,
        ]);
    }
}
