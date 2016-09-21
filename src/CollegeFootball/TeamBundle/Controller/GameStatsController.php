<?php

namespace CollegeFootball\TeamBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use CollegeFootball\TeamBundle\Entity\Game;
use CollegeFootball\TeamBundle\Form\Type\GameStatsType;

/**
 * @Route("/stats")
 * @Security("is_granted('ROLE_MANAGE')")
 */
class GameStatsController extends Controller
{
    /**
     * @Route("/{season}", name="collegefootball_team_game_stats_index", defaults={"season": null})
     * @Route("/{season}/week/{week}", name="collegefootball_team_game_stats_index_week", defaults={"week": null})
     */
    public function indexAction($season = null, $week = null)
    {
        $result      = $this->get('collegefootball.team.week')->currentWeek($season, $week);
        $week        = $result['week'];
        $season      = $result['season'];
        $seasonWeeks = $result['seasonWeeks'];

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

        $gamesNeedingStats = [];

        foreach ($games as $game) {
            if ($game->getWinningTeam() && (! $game->getStats() || (! array_key_exists('totalOffenseYards', $game->getStats()['homeStats']) || ! $game->getStats()['homeStats']['totalOffenseYards']))) {
                $gamesNeedingStats[] = $game;
            }
        }

        return $this->render('CollegeFootballTeamBundle:Game:stats.html.twig', [
            'games'        => $gamesNeedingStats,
            'season'       => $season,
            'week'         => $week,
            'season_weeks' => $seasonWeeks,
        ]);
    }

    /**
     * @Route("/game/{game}/add", name="collegefootball_team_game_stats_add")
     */
    public function addAction(Game $game, Request $request)
    {
        $form = $this->createForm(GameStatsType::class, $game);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->flush();

            $this->addFlash('success', 'Game stats saved');
            return $this->redirectToRoute('collegefootball_team_game_show', [
                'game' => $game->getId(),
            ]);
        }

        return $this->render('CollegeFootballTeamBundle:Game:addEditStats.html.twig', [
            'game' => $game,
            'form' => $form->createView(),
        ]);
    }
}
