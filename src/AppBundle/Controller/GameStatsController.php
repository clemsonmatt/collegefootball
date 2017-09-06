<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use AppBundle\Entity\Game;
use AppBundle\Form\Type\GameStatsType;
use AppBundle\Service\WeekService;

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
    public function indexAction($season = null, $week = null, WeekService $weekService)
    {
        $result      = $weekService->currentWeek($season, $week);
        $week        = $result['week'];
        $season      = $result['season'];
        $seasonWeeks = $result['seasonWeeks'];

        $em         = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('AppBundle:Game');
        $games      = $repository->findGamesByWeek($week);

        $gamesNeedingStats = [];

        foreach ($games as $game) {
            if ($game['stats'] === null || (! array_key_exists('totalOffenseYards', $game['stats']['homeStats']) || ! $game['stats']['homeStats']['totalOffenseYards'])) {
                $gamesNeedingStats[] = $game;
            }
        }

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
    public function addAction(Game $game, Request $request)
    {
        $form = $this->createForm(GameStatsType::class, $game);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->flush();

            $this->addFlash('success', 'Game stats saved');
            return $this->redirectToRoute('app_game_show', [
                'game' => $game->getId(),
            ]);
        }

        return $this->render('AppBundle:Game:addEditStats.html.twig', [
            'game' => $game,
            'form' => $form->createView(),
        ]);
    }
}