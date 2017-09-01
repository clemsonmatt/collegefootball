<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use AppBundle\Entity\Person;
use AppBundle\Entity\Week;
use AppBundle\Form\Type\PersonType;
use AppBundle\Entity\Game;
use AppBundle\Service\EmailService;
use AppBundle\Service\WeekService;

/**
 * @Route("/manage")
 * @Security("is_granted('ROLE_MANAGE')")
 */
class ManageController extends Controller
{
    /**
     * @Route("/people", name="app_manage_people")
     */
    public function indexAction()
    {
        $em         = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('AppBundle:Person');
        $people     = $repository->findBy([], ['lastName' => 'ASC']);

        return $this->render('AppBundle:Manage:people.html.twig', [
            'people' => $people,
        ]);
    }

    /**
     * @Route("/people/add", name="app_manage_people_add")
     */
    public function addPersonAction(Request $request)
    {
        $person = new Person();

        $form = $this->createForm(PersonType::class, $person, [
            'show_password' => false,
        ]);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($person);
            $em->flush();

            return $this->redirectToRoute('app_manage_people');
        }

        return $this->render('AppBundle:Manage:addPerson.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/pickem-reminder-email", name="app_manage_pickem_reminder_email")
     */
    public function pickemReminderEmailAction(EmailService $emailService)
    {
        $emailService->sendPickemReminder();

        $this->addFlash('success', 'Pickem reminder sent');
        return $this->redirectToRoute('app_manage_people');
    }

    /**
     * @Route("/weekly-pickem", name="app_manage_pickem")
     * @Route("/weekly-pickem/{season}/week/{week}", name="app_manage_pickem_week")
     */
    public function pickemAction($season = null, $week = null, WeekService $weekService)
    {
        $result      = $weekService->currentWeek($season, $week);
        $week        = $result['week'];
        $season      = $result['season'];
        $seasonWeeks = $result['seasonWeeks'];

        $em         = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('AppBundle:Game');
        $games      = $repository->findGamesByWeek($week);
        $pickemGames = $repository->findGamesByWeek($week, false, true);

        return $this->render('AppBundle:Manage:pickem.html.twig', [
            'season'       => $season,
            'weeks'        => $seasonWeeks,
            'week'         => $week,
            'games'        => $games,
            'pickem_games' => $pickemGames,
        ]);
    }

    /**
     * @Route("/weekly-pickem/{week_id}/{game_id}/update-game", name="app_manage_pickem_update")
     * @ParamConverter("week", options={"id" = "week_id"})
     * @ParamConverter("game", options={"id" = "game_id"})
     */
    public function addPickemGame(Week $week, Game $game)
    {
        $em         = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('AppBundle:Game');
        $games      = $repository->findGamesByWeek($week, false, true);

        foreach ($games as $week_game) {
            if ($week_game['id'] == $game->getId()) {
                // already in games, so remove
                $game->setPickemGame(false);
                $em->flush();

                return new JsonResponse(['code' => 200, 'success' => true]);
            }
        }

        // add to weekly pickem
        $game->setPickemGame(true);
        $em->flush();

        return new JsonResponse(['code' => 200, 'success' => true]);
    }
}
