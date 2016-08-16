<?php

namespace CollegeFootball\TeamBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

use CollegeFootball\TeamBundle\Entity\Game;
use CollegeFootball\TeamBundle\Entity\Team;
use CollegeFootball\TeamBundle\Form\Type\GameType;

/**
 * @Route("/game")
 */
class GameController extends Controller
{
    /**
     * @Route("/", name="collegefootball_team_game_index")
     */
    public function indexAction()
    {
        $em         = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('CollegeFootballTeamBundle:Game');
        $games      = $repository->findBy([], ['date' => 'asc']);

        return $this->render('CollegeFootballTeamBundle:Game:index.html.twig', [
            'games' => $games,
        ]);
    }

    /**
     * @Route("/{game}/show", name="collegefootball_team_game_show")
     */
    public function showAction(Game $game)
    {
        return $this->render('CollegeFootballTeamBundle:Game:show.html.twig', [
            'game' => $game,
        ]);
    }

    /**
     * @Route("/add", name="collegefootball_team_game_add")
     * @Route("/add/{slug}/team", name="collegefootball_team_game_add_team")
     */
    public function addAction(Request $request, Team $team = null)
    {
        $games = [];
        if ($team) {
            $em         = $this->getDoctrine()->getManager();
            $repository = $em->getRepository('CollegeFootballTeamBundle:Game');
            $games      = $repository->findGamesByTeam($team);
        }

        $game = new Game();
        $form = $this->createForm(GameType::class, $game);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($game);
            $em->flush();

            $this->addFlash('success', 'Game added');

            if ($team) {
                return $this->redirectToRoute('collegefootball_team_game_add_team', [
                    'slug' => $team->getSlug(),
                ]);
            }

            return $this->redirectToRoute('collegefootball_team_game_show', [
                'game' => $game->getId(),
            ]);
        }

        return $this->render('CollegeFootballTeamBundle:Game:addEdit.html.twig', [
            'form'  => $form->createView(),
            'team'  => $team,
            'games' => $games,
        ]);
    }

    /**
     * @Route("/{game}/edit", name="collegefootball_team_game_edit")
     */
    public function editAction(Game $game, Request $request)
    {
        $form = $this->createForm(GameType::class, $game);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->flush();

            $this->addFlash('success', 'Game saved');
            return $this->redirectToRoute('collegefootball_team_game_show', [
                'game' => $game->getId(),
            ]);
        }

        return $this->render('CollegeFootballTeamBundle:Game:addEdit.html.twig', [
            'form' => $form->createView(),
            'game' => $game,
        ]);
    }

    /**
     * @Route("/{game}/remove", name="collegefootball_team_game_remove")
     */
    public function removeAction(Game $game)
    {
        $em = $this->getDoctrine()->getManager();
        $em->remove($game);
        $em->flush();

        $this->addFlash('warning', 'Game removed');
        return $this->redirectToRoute('collegefootball_team_game_index');
    }

    /**
     * @Route("/team/{slug}/game/{game}/outcome", name="collegefootball_team_game_outcome")
     */
    public function outcomeAction(Team $team, Game $game)
    {
        $game->setWinningTeam($team);

        $em = $this->getDoctrine()->getManager();
        $em->flush();

        $this->addFlash('success', 'Winning team set');
        return $this->redirectToRoute('collegefootball_team_game_show', [
            'game' => $game->getId(),
        ]);
    }
}
