<?php

namespace CollegeFootball\TeamBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

use CollegeFootball\TeamBundle\Entity\Game;
use CollegeFootball\TeamBundle\Form\Type\GameStatsType;

/**
 * @Route("/game/{game}/stats")
 */
class GameStatsController extends Controller
{
    /**
     * @Route("/add", name="collegefootball_team_game_stats_add")
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
