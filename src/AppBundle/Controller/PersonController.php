<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Encoder\BCryptPasswordEncoder;

use AppBundle\Entity\Person;
use AppBundle\Entity\Prediction;
use AppBundle\Entity\Week;
use AppBundle\Form\Type\PersonType;
use AppBundle\Entity\Game;
use AppBundle\Entity\Team;
use AppBundle\Service\PickemService;
use AppBundle\Service\WeekService;

/**
 * @Route("/person")
 */
class PersonController extends Controller
{
    /**
     * @Route("/pickem", name="app_person_pickem")
     */
    public function pickemAction()
    {
        return $this->redirectToRoute('app_person_show', [
            'username' => $this->getUser()->getUsername(),
        ]);
    }

    /**
     * @Route("/{username}/show", name="app_person_show")
     * @Route("/{username}/show/{season}/week/{week}", name="app_person_show_week")
     * @Security("user == person or is_granted('ROLE_MANAGE')")
     */
    public function showAction(Person $person, $season = null, $week = null, WeekService $weekService, PickemService $pickemService)
    {
        $result      = $weekService->currentWeek($season, $week);
        $week        = $result['week'];
        $season      = $result['season'];
        $seasonWeeks = $result['seasonWeeks'];

        /* can't view future weeks */
        $today = new \DateTime("now");

        if ($week->getStartDate()->format('U') > $today->format('U')) {
            $this->addFlash('warning', 'Cannot view future weeks');

            $result      = $weekService->currentWeek();
            $week        = $result['week'];
            $season      = $result['season'];
            $seasonWeeks = $result['seasonWeeks'];
        }

        $em         = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('AppBundle:Game');
        $games      = $repository->findGamesByWeek($week, false, true);

        $weekWinners   = $pickemService->predictedWeekWinnersByPerson($person, $week);
        $gamePicks     = $pickemService->picksByWeek($week);

        /* find games won/lost this week */
        $weekWinPicks  = 0;
        $weekLosePicks = 0;
        foreach ($games as $game) {
            $winningTeam = $game['winningTeam'];

            if ($winningTeam && in_array($winningTeam['slug'], $weekWinners)) {
                $weekWinPicks++;
            } elseif ($winningTeam['id']) {
                $weekLosePicks++;
            }
        }

        /* leaderboard */
        $leaderboard = $pickemService->leaderboardRank($person);

        return $this->render('AppBundle:Person:show.html.twig', [
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
            'current_score'   => $leaderboard['currentScore'],
            'highest_score'   => $leaderboard['highestScore'],
        ]);
    }

    /**
     * @Route("/{username}/edit", name="app_person_edit")
     * @Security("user == person or is_granted('ROLE_MANAGE')")
     */
    public function editAction(Person $person, Request $request)
    {
        $form = $this->createForm(PersonType::class, $person, [
            'show_password' => false,
        ]);

        $form->handleRequest($request);

        if ($form->isValid()) {
            // clear out phone data if user deletes
            if (! $person->getPhoneNumber()) {
                $person->setPhoneCarrier(null);

                // toggle subscriptions if signed up for texts
                if ($person->hasTextSubscription()) {
                    $person->setTextSubscription(false);
                    $person->setEmailSubscription(true);
                }
            }

            $em = $this->getDoctrine()->getManager();
            $em->flush();

            $this->addFlash('success', 'Profile saved');

            return $this->redirectToRoute('app_person_edit', [
                'username' => $person->getUsername(),
            ]);
        }

        return $this->render('AppBundle:Person:edit.html.twig', [
            'form'    => $form->createView(),
            'person'  => $person,
            'heading' => 'Edit Profile',
        ]);
    }

    /**
     * @Route("/{username}/change-password", name="app_person_change_password")
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
                return $this->redirectToRoute('app_person_change_password', [
                    'username' => $person->getUsername(),
                ]);
            }

            $password = $passwordEncoder->encodePassword($person, $person->getPassword());
            $person->setPassword($password);

            $em = $this->getDoctrine()->getManager();
            $em->flush();

            $this->addFlash('success', 'Password changed');

            return $this->redirectToRoute('app_person_show', [
                'username' => $person->getUsername(),
            ]);
        }

        return $this->render('AppBundle:Person:edit.html.twig', [
            'form'    => $form->createView(),
            'person'  => $person,
            'heading' => 'Change Password',
        ]);
    }

    /**
     * @Route("/{username}/notifications", name="app_person_manage_notifications")
     * @Security("user == person or is_granted('ROLE_MANAGE')")
     */
    public function manageNotificationsAction(Person $person)
    {
        return $this->render('AppBundle:Person:notifications.html.twig', [
            'person' => $person,
        ]);
    }

    /**
     * @Route("/{username}/{type}/toggle-notification", name="app_person_toggle_notification", requirements={"type": "email|phone"})
     * @Security("user == person or is_granted('ROLE_MANAGE')")
     */
    public function toggleNotificaitonAction(Person $person, $type)
    {
        $toggle = false;

        if ($type == 'email') {
            $emailSub = $person->hasEmailSubscription();
            $person->setEmailSubscription(! $emailSub);

            if (! $emailSub && $person->hasTextSubscription()) {
                $person->setTextSubscription(false);
            }
        } else {
            $textSub = $person->hasTextSubscription();
            $person->setTextSubscription(! $textSub);

            if (! $textSub && $person->hasEmailSubscription()) {
                $person->setEmailSubscription(false);
            }
        }

        if ($person->hasEmailSubscription() || $person->hasTextSubscription()) {
            $toggle = true;
        }

        $em = $this->getDoctrine()->getManager();
        $em->flush();

        $response = ['code' => 100, 'success' => true, 'toggle' => $toggle];
        return new JsonResponse($response);
    }

    /**
     * @Route("/{username}/game/{game}/winner/{slug}", name="app_person_prediction")
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
        $repository = $em->getRepository('AppBundle:Prediction');
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
