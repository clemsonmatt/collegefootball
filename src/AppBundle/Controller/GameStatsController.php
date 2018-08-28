<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use AppBundle\Entity\Game;
use AppBundle\Form\Type\GameStatsType;
use AppBundle\Service\StatsService;

/**
 * @Route("/stats")
 * @Security("is_granted('ROLE_MANAGE')")
 */
class GameStatsController extends Controller
{
    /**
     * @Route("/{season}", name="app_game_stats_index", defaults={"season": null})
     * @Route("/{season}/week/{week}", name="app_game_stats_index_week", defaults={"week": null})
     */
    public function indexAction($season = null, $week = null, StatsService $statsService)
    {
        list($gamesNeedingStats, $season, $week, $seasonWeeks) = $statsService->gamesMissingStats($season, $week);

        return $this->render('AppBundle:Game:stats.html.twig', [
            'games'        => $gamesNeedingStats,
            'season'       => $season,
            'week'         => $week,
            'season_weeks' => $seasonWeeks,
        ]);
    }

    /**
     * @Route("/game/{game}/add", name="app_game_stats_add")
     */
    public function addAction(Game $game, Request $request, StatsService $statsService)
    {
        $form = $this->createForm(GameStatsType::class, $game);

        $form->handleRequest($request);

        if ($form->isValid()) {
            // set the winner
            $game->setWinnerFromStats();

            $em = $this->getDoctrine()->getManager();
            $em->flush();

            $this->addFlash('success', 'Game stats saved');

            // get the next game that needs stats
            $nextGameId = $statsService->gamesMissingStats(null, null, true);

            if ($nextGameId) {
                return $this->redirectToRoute('app_game_stats_add', [
                    'game' => $nextGameId,
                ]);
            }

            return $this->redirectToRoute('app_game_stats_index');
        }

        return $this->render('AppBundle:Game:addEditStats.html.twig', [
            'game' => $game,
            'form' => $form->createView(),
        ]);
    }
}
