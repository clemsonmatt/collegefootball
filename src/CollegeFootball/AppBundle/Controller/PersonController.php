<?php

namespace CollegeFootball\AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use CollegeFootball\AppBundle\Entity\Person;
use CollegeFootball\AppBundle\Entity\Prediction;
use CollegeFootball\AppBundle\Entity\Week;
use CollegeFootball\TeamBundle\Entity\Game;
use CollegeFootball\TeamBundle\Entity\Team;

/**
 * @Route("/person")
 */
class PersonController extends Controller
{
    /**
     * @Route("/{username}/show/{week}", name="collegefootball_person_show")
     * @Security("user == person or is_granted('ROLE_MANAGE')")
     */
    public function showAction(Person $person, Week $week = null)
    {
        $weekService = $this->get('collegefootball.team.week');
        $weekResult  = $weekService->currentWeek();

        $week = $weekResult['week'];

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


        $repository           = $em->getRepository('CollegeFootballAppBundle:Prediction');
        $predictedWeekWinners = $repository->createQueryBuilder('p')
            ->select('t.slug')
            ->join('p.person', 'person')
            ->join('p.team', 't')
            ->join('p.game', 'g')
            ->where('person.username = :username')
            ->andWhere('g.date >= :startDate')
            ->andWhere('g.date <= :endDate')
            ->orderBy('g.date, g.time')
            ->setParameter('username', $person->getUsername())
            ->setParameter('startDate', $week->getStartDate())
            ->setParameter('endDate', $week->getEndDate())
            ->getQuery()
            ->getResult();

        $weekWinners = [];
        foreach ($predictedWeekWinners as $weekWinner) {
            $weekWinners[] = $weekWinner['slug'];
        }

        $pickemService = $this->get('collegefootball.app.pickem');
        $gamePicks     = $pickemService->picksByWeek($week);

        return $this->render('CollegeFootballAppBundle:Person:show.html.twig', [
            'person'       => $person,
            'week'         => $week,
            'weeks'        => $weekResult['seasonWeeks'],
            'games'        => $games,
            'week_winners' => $weekWinners,
            'game_picks'   => $gamePicks,
        ]);
    }

    /**
     * @Route("/{username}/game/{game}/winner/{slug}", name="collegefootball_person_prediction")
     * @Security("user == person or is_granted('ROLE_MANAGE')")
     */
    public function gamePredictionAction(Person $person, Game $game, Team $team, Request $request)
    {
        $now = new \DateTime("now");

        /* make sure game hasn't started */
        if ($game->getDate()->format('U') <= $now->format('U')) {
            if ($game->getDate()->format('Y-m-d') < $now->format('Y-m-d') || ($game->getDate()->format('Y-m-d') == $now->format('Y-m-d') && $game->getTime() < $now->format('h:i A'))) {
                $response = ['code' => 100, 'error' => true, 'errorMessage' => 'Cannot pick after game has begun.'];
                return new JsonResponse($response);
            }
        }

        $em         = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('CollegeFootballAppBundle:Prediction');
        $prediction = $repository->findOneBy([
            'person' => $person,
            'game'   => $game
        ]);

        if ($prediction) {
            $em->remove($prediction);
            $em->flush();
        }

        $prediction = new Prediction();
        $prediction->setPerson($person);
        $prediction->setGame($game);
        $prediction->setTeam($team);

        $em->persist($prediction);
        $em->flush();

        $response = ['code' => 100, 'success' => true, 'data' => ['person' => (string)$person, 'game' => (string)$game, 'team' => (string)$team]];
        return new JsonResponse($response);
    }
}
