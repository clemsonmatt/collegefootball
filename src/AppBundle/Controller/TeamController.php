<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use AppBundle\Entity\Team;
use AppBundle\Form\Type\TeamType;

/**
 * @Route("/team")
 */
class TeamController extends Controller
{
    /**
     * @Route("/", name="app_team_index")
     */
    public function indexAction()
    {
        $em         = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('AppBundle:Team');
        $teams      = $repository->findBy([], ['name' => 'asc']);

        return $this->render('AppBundle:Team:index.html.twig', [
            'teams' => $teams,
        ]);
    }

    /**
     * @Route("/{slug}/show", name="app_team_show")
     */
    public function showAction(Team $team)
    {
        $em         = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('AppBundle:Game');
        $games      = $repository->findGamesByTeam($team);

        $weekService = $this->get('collegefootball.team.week');
        $currentWeek = $weekService->currentWeek()['week'];

        $nextGame = $repository->createQueryBuilder('g')
            ->where('g.date >= :startDate')
            ->andWhere('g.homeTeam = :team OR g.awayTeam = :team')
            ->andWhere('g.winningTeam IS NULL')
            ->setParameter('startDate', $currentWeek->getStartDate())
            ->setParameter('team', $team)
            ->orderBy('g.date')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        $statsService   = $this->get('collegefootball.team.stats');
        $gameComparison = $statsService->gameComparison($nextGame);

        $conferenceService = $this->get('collegefootball.team.conference');
        $conferenceRanking = $conferenceService->teamRankInConference($team->getConference(), false);

        return $this->render('AppBundle:Team:show.html.twig', [
            'team'               => $team,
            'games'              => $games,
            'conference_ranking' => $conferenceRanking,
            'next_game'          => $nextGame,
            'game_comparison'    => $gameComparison,
        ]);
    }

    /**
     * @Route("/add", name="app_team_add")
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
            return $this->redirectToRoute('app_team_show', [
                'slug' => $team->getSlug(),
            ]);
        }

        return $this->render('AppBundle:Team:addEdit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{slug}/edit", name="app_team_edit")
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
            return $this->redirectToRoute('app_team_show', [
                'slug' => $team->getSlug(),
            ]);
        }

        return $this->render('AppBundle:Team:addEdit.html.twig', [
            'form' => $form->createView(),
            'team' => $team,
        ]);
    }

    /**
     * @Route("/{slug}/remove", name="app_team_remove")
     * @Security("is_granted('ROLE_MANAGE')")
     */
    public function removeAction(Team $team)
    {
        $em = $this->getDoctrine()->getManager();
        $em->remove($team);
        $em->flush();

        $this->addFlash('warning', 'Team removed');
        return $this->redirectToRoute('app_team_index');
    }

    /**
     * @Route("/{slug}/schedule", name="app_team_schedule")
     */
    public function scheduleAction(Team $team)
    {
        $em         = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('AppBundle:Game');
        $games      = $repository->findGamesByTeam($team);

        $repository      = $em->getRepository('AppBundle:Prediction');
        $userPredictions = $repository->findBy([
            'person' => $this->getUser(),
            'game'   => $games,
        ]);

        $userPredictionsByGame = [];
        foreach ($userPredictions as $userPrediction) {
            $userPredictionsByGame[$userPrediction->getGame()->getId()] = $userPrediction->getTeam();
        }

        return $this->render('AppBundle:Team:schedule.html.twig', [
            'games'            => $games,
            'team'             => $team,
            'user_predictions' => $userPredictionsByGame,
        ]);
    }

    /**
     * @Route("/{slug}/rankings", name="app_team_rankings")
     */
    public function rankingsAction(Team $team)
    {
        $conferenceService = $this->get('collegefootball.team.conference');
        $conferenceRanking = $conferenceService->teamRankInConference($team->getConference());

        return $this->render('AppBundle:Team:rankings.html.twig', [
            'team'               => $team,
            'conference_ranking' => $conferenceRanking
        ]);
    }

    /**
     * @Route("/{slug}/statistics", name="app_team_statistics")
     */
    public function statisticsAction(Team $team)
    {
        $statsService = $this->get('collegefootball.team.stats');
        $stats        = $statsService->statsForTeam($team);

        return $this->render('AppBundle:Team:statistics.html.twig', [
            'team'  => $team,
            'stats' => $stats,
        ]);
    }

    /**
     * @Route("/search", name="app_team_search")
     */
    public function searchAction(Request $request)
    {
        $searchName = $request->query->get('searchName');
        $data       = $this->searchForTeam($searchName);

        $response = ['code' => 100, 'success' => true, 'data' => $data];

        return new JsonResponse($response);
    }

    /**
     * @Route("/manual-search", name="app_team_manual_search")
     */
    public function manualSearchAction(Request $request)
    {
        $searchName = $request->request->get('searchName');
        $teams      = $this->searchForTeam($searchName);

        return $this->render('AppBundle:Team:manualSearch.html.twig', [
            'teams'       => $teams,
            'search_name' => $searchName,
        ]);
    }

    private function searchForTeam($searchName)
    {
        $em         = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('AppBundle:Team');
        $teams      = $repository->createQueryBuilder('t')
            ->where('t.name LIKE :searchName')
            ->setParameter('searchName', '%'.$searchName.'%')
            ->getQuery()
            ->getResult();

        $data = [];
        foreach ($teams as $team) {
            $route = $this->generateUrl('app_team_show', ['slug' => $team->getSlug()]);

            $data[] = [
                'route' => $route,
                'name'  => $team->getName(),
            ];
        }

        return $data;
    }
}
