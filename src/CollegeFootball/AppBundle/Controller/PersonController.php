<?php

namespace CollegeFootball\AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
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
     * @Route("/")
     */
    public function indexAction()
    {
        return $this->render('.html.twig');
    }

    /**
     * @Route("/{username}/show/{week}", name="collegefootball_person_show")
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


        // $query = 'SELECT t.slug
        //     FROM collegefootball.prediction p
        //     JOIN collegefootball.person person on p.person_id = person.id
        //     JOIN collegefootball.team t on p.team_id = t.id
        //     JOIN collegefootball.game g on p.game_id = g.id
        //     WHERE person.username = :username
        //     AND g.date >= :startDate
        //     AND g.date <= :endDate
        //     ORDER BY g.date, g.time
        // ';

        // $statement = $em->getConnection()->prepare($query);
        // $statement->bindValue('username', $person->getUsername());
        // $statement->bindValue('startDate', $week->getStartDate()->format('y-m-d'));
        // $statement->bindValue('endDate', $week->getEndDate()->format('y-m-d'));
        // $statement->execute();
        // $predictedWeekWinners = $statement->fetchAll();

        $weekWinners = [];
        // foreach ($predictedWeekWinners as $weekWinner) {
        //     $weekWinners[] = $weekWinner['slug'];
        // }

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
     */
    public function gamePredictionAction(Person $person, Game $game, Team $team, Request $request)
    {
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
