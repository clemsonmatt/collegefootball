<?php

namespace CollegeFootball\TeamBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
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

        $weekService = $this->get('collegefootball.team.week');
        $currentWeek = $weekService->currentWeek()['week'];

        $nextGame = $repository->createQueryBuilder('g')
            ->where('g.date >= :startDate')
            ->andWhere('g.date <= :endDate')
            ->andWhere('g.homeTeam = :team OR g.awayTeam = :team')
            ->setParameter('startDate', $currentWeek->getStartDate())
            ->setParameter('endDate', $currentWeek->getEndDate())
            ->setParameter('team', $team)
            ->getQuery()
            ->getSingleResult();

        $conferenceService = $this->get('collegefootball.team.conference');
        $conferenceRanking = $conferenceService->teamRankInConference($team->getConference());

        return $this->render('CollegeFootballTeamBundle:Team:show.html.twig', [
            'team'               => $team,
            'games'              => $games,
            'conference_ranking' => $conferenceRanking,
            'next_game'          => $nextGame,
        ]);
    }

    /**
     * @Route("/add", name="collegefootball_team_add")
     * @Security("is_granted('ROLE_MANAGE')")
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
     * @Security("is_granted('ROLE_MANAGE')")
     */
    public function editAction(Team $team, Request $request)
    {
        $form = $this->createForm(TeamType::class, $team);

        $form->handleRequest($request);

        if ($form->isValid()) {
            if ($form['logo']->getData() !== null) {
                $uploadService = $this->get('collegefootball.team.upload');
                $imagePath     = $uploadService->uploadImage($form['logo']->getData(), 'team');
                $team->setLogo($imagePath);
            }

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
     * @Security("is_granted('ROLE_MANAGE')")
     */
    public function removeAction(Team $team)
    {
        $em = $this->getDoctrine()->getManager();
        $em->remove($team);
        $em->flush();

        $this->addFlash('warning', 'Team removed');
        return $this->redirectToRoute('collegefootball_team_index');
    }

    /**
     * @Route("/{slug}/schedule", name="collegefootball_team_schedule")
     */
    public function scheduleAction(Team $team)
    {
        $em         = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('CollegeFootballTeamBundle:Game');
        $games      = $repository->findGamesByTeam($team);

        return $this->render('CollegeFootballTeamBundle:Team:schedule.html.twig', [
            'games' => $games,
            'team'  => $team,
        ]);
    }

    /**
     * @Route("/{slug}/rankings", name="collegefootball_team_rankings")
     */
    public function rankingsAction(Team $team)
    {
        $conferenceService = $this->get('collegefootball.team.conference');
        $conferenceRanking = $conferenceService->teamRankInConference($team->getConference());

        return $this->render('CollegeFootballTeamBundle:Team:rankings.html.twig', [
            'team'               => $team,
            'conference_ranking' => $conferenceRanking
        ]);
    }

    /**
     * @Route("/{slug}/statistics", name="collegefootball_team_statistics")
     */
    public function statisticsAction(Team $team)
    {
        $statsService = $this->get('collegefootball.team.stats');
        $stats        = $statsService->statsForTeam($team);

        return $this->render('CollegeFootballTeamBundle:Team:statistics.html.twig', [
            'team'  => $team,
            'stats' => $stats,
        ]);
    }

    /**
     * @Route("/search", name="collegefootball_team_search")
     */
    public function searchAction(Request $request)
    {
        $searchName = $request->query->get('searchName');

        $em         = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('CollegeFootballTeamBundle:Team');
        $teams      = $repository->createQueryBuilder('t')
            ->where('t.name LIKE :searchName')
            ->setParameter('searchName', '%'.$searchName.'%')
            ->getQuery()
            ->getResult();

        $data = [];
        foreach ($teams as $team) {
            $route = $this->generateUrl('collegefootball_team_show', ['slug' => $team->getSlug()]);

            $data[] = [
                'route' => $route,
                'name'  => $team->getName(),
            ];
        }

        $response = ['code' => 100, 'success' => true, 'data' => $data];
        return new JsonResponse($response);
    }
}
