<?php

namespace CollegeFootball\TeamBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

use CollegeFootball\TeamBundle\Entity\Team;
use CollegeFootball\TeamBundle\Form\Type\TeamType;

/**
 * @Route("/team")
 */
class TeamController extends Controller
{
    /**
     * @Route("/", name="collegefootball_team_index")
     */
    public function indexAction()
    {
        $em         = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('CollegeFootballTeamBundle:Team');
        $teams      = $repository->findBy([], ['name' => 'asc']);

        return $this->render('CollegeFootballTeamBundle:Team:index.html.twig', [
            'teams' => $teams,
        ]);
    }

    /**
     * @Route("/{slug}/show", name="collegefootball_team_show")
     */
    public function showAction(Team $team)
    {
        $em         = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('CollegeFootballTeamBundle:Game');
        $games      = $repository->findGamesByTeam($team);

        return $this->render('CollegeFootballTeamBundle:Team:show.html.twig', [
            'team'  => $team,
            'games' => $games,
        ]);
    }

    /**
     * @Route("/add", name="collegefootball_team_add")
     */
    public function addAction(Request $request)
    {
        $team = new Team();
        $form = $this->createForm(TeamType::class, $team);

        $form->handleRequest($request);

        if ($form->isValid()) {
            if ($form['logo']->getData() !== null) {
                $uploadService = $this->get('collegefootball.team.upload');
                $imagePath     = $uploadService->uploadImage($form['logo']->getData(), 'team');
                $team->setLogo($imagePath);
            }

            $em = $this->getDoctrine()->getManager();
            $em->persist($team);
            $em->flush();

            $this->addFlash('success', 'Team added');
            return $this->redirectToRoute('collegefootball_team_show', [
                'slug' => $team->getSlug(),
            ]);
        }

        return $this->render('CollegeFootballTeamBundle:Team:addEdit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{slug}/edit", name="collegefootball_team_edit")
     */
    public function editAction(Team $team, Request $request)
    {
        $form = $this->createForm(TeamType::class, $team);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->flush();

            $this->addFlash('success', 'Team saved');
            return $this->redirectToRoute('collegefootball_team_show', [
                'slug' => $team->getSlug(),
            ]);
        }

        return $this->render('CollegeFootballTeamBundle:Team:addEdit.html.twig', [
            'form' => $form->createView(),
            'team' => $team,
        ]);
    }

    /**
     * @Route("/{slug}/remove", name="collegefootball_team_remove")
     */
    public function removeAction(Team $team)
    {
        $em = $this->getDoctrine()->getManager();
        $em->remove($team);
        $em->flush();

        $this->addFlash('warning', 'Team removed');
        return $this->redirectToRoute('collegefootball_team_index');
    }
}
