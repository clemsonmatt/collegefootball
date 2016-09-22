<?php

namespace CollegeFootball\AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Encoder\BCryptPasswordEncoder;

use CollegeFootball\AppBundle\Entity\Person;
use CollegeFootball\AppBundle\Entity\Prediction;
use CollegeFootball\AppBundle\Entity\Week;
use CollegeFootball\AppBundle\Form\Type\PersonType;
use CollegeFootball\TeamBundle\Entity\Game;
use CollegeFootball\TeamBundle\Entity\Team;

/**
 * @Route("/person")
 */
class PersonController extends Controller
{
    /**
     * @Route("/{username}/show", name="collegefootball_person_show")
     * @Route("/{username}/show/{season}/week/{week}", name="collegefootball_person_show_week")
     * @Security("user == person or is_granted('ROLE_MANAGE')")
     */
    public function showAction(Person $person, $season = null, $week = null)
    {
        $result      = $this->get('collegefootball.team.week')->currentWeek($season, $week);
        $week        = $result['week'];
        $season      = $result['season'];
        $seasonWeeks = $result['seasonWeeks'];

        /* can't view future weeks */
        $today = new \DateTime("now");

        if ($week->getStartDate()->format('U') > $today->format('U')) {
            $this->addFlash('warning', 'Cannot view future weeks');

            $result      = $this->get('collegefootball.team.week')->currentWeek();
            $week        = $result['week'];
            $season      = $result['season'];
            $seasonWeeks = $result['seasonWeeks'];
        }

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


        $pickemService = $this->get('collegefootball.app.pickem');
        $weekWinners   = $pickemService->predictedWeekWinnersByPerson($person, $week);
        $gamePicks     = $pickemService->picksByWeek($week);

        /* find games won/lost this week */
        $weekWinPicks  = 0;
        $weekLosePicks = 0;
        foreach ($games as $game) {
            $winningTeam = $game->getWinningTeam();
            if ($winningTeam && in_array($winningTeam->getSlug(), $weekWinners)) {
                $weekWinPicks++;
            } elseif ($winningTeam) {
                $weekLosePicks++;
            }
        }

        /* leaderboard */
        $leaderboard = $pickemService->leaderboardRank($person);

        return $this->render('CollegeFootballAppBundle:Person:show.html.twig', [
            'person'          => $person,
            'week'            => $week,
            'season_weeks'    => $seasonWeeks,
            'season'          => $season,
            'games'           => $games,
            'week_winners'    => $weekWinners,
            'game_picks'      => $gamePicks,
            'week_win_picks'  => $weekWinPicks,
            'week_lose_picks' => $weekLosePicks,
            'people_rank'     => $leaderboard['peopleByRank'],
            'current_rank'    => $leaderboard['currentRank'],
        ]);
    }

    /**
     * @Route("/{username}/edit", name="collegefootball_person_edit")
     * @Security("user == person or is_granted('ROLE_MANAGE')")
     */
    public function editAction(Person $person, Request $request)
    {
        $form = $this->createForm(PersonType::class, $person, [
            'show_password' => false,
        ]);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->flush();

            return $this->redirectToRoute('collegefootball_person_show', [
                'username' => $person->getUsername(),
            ]);
        }

        return $this->render('CollegeFootballAppBundle:Person:edit.html.twig', [
            'form'    => $form->createView(),
            'person'  => $person,
            'heading' => 'Edit Profile',
        ]);
    }

    /**
     * @Route("/{username}/change-password", name="collegefootball_person_change_password")
     * @Security("user == person or is_granted('ROLE_MANAGE')")
     */
    public function changePasswordAction(Person $person, Request $request)
    {
        $currentPassword = $person->getPassword();

        $form = $this->createForm(PersonType::class, $person, [
            'only_password' => true,
        ]);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $passwordEncoder = $this->get('security.password_encoder');
            $basePasswordEncoder = new BCryptPasswordEncoder(13);

            /* check current password matches */
            if (! $basePasswordEncoder->isPasswordValid($currentPassword, $form['currentPassword']->getData(), $person->getSalt())) {
                $this->addFlash('warning', 'Current password must match');
                return $this->redirectToRoute('collegefootball_person_change_password', [
                    'username' => $person->getUsername(),
                ]);
            }

            $password = $passwordEncoder->encodePassword($person, $person->getPassword());
            $person->setPassword($password);

            $em = $this->getDoctrine()->getManager();
            $em->flush();

            $this->addFlash('success', 'Password changed');

            return $this->redirectToRoute('collegefootball_person_show', [
                'username' => $person->getUsername(),
            ]);
        }

        return $this->render('CollegeFootballAppBundle:Person:edit.html.twig', [
            'form'    => $form->createView(),
            'person'  => $person,
            'heading' => 'Change Password',
        ]);
    }

    /**
     * @Route("/{username}/game/{game}/winner/{slug}", name="collegefootball_person_prediction")
     * @Security("user == person or is_granted('ROLE_MANAGE')")
     */
    public function gamePredictionAction(Person $person, Game $game, Team $team, Request $request)
    {
        $now = new \DateTime("now");
        $now = $now->modify('-4 hours');

        /* make sure game hasn't started */
        if ($game->getDate()->format('U') <= $now->format('U')) {
            $gameTime = new \DateTime($game->getTime());
            if ($game->getDate()->format('Y-m-d') < $now->format('Y-m-d') || ($game->getDate()->format('Y-m-d') == $now->format('Y-m-d') && $gameTime->format('U') < $now->format('U'))) {
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

        $response = ['code' => 100, 'success' => true, 'data' => ['person' => (string)$person, 'game' => (string)$game, 'team' => (string)$team, 'now' => $now->format('h:i A')]];
        return new JsonResponse($response);
    }
}
