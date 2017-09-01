<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use AppBundle\Entity\Gameday;
use AppBundle\Entity\Week;
use AppBundle\Form\Type\GamedayType;

/**
 * @Route("/gameday")
 */
class GamedayController extends Controller
{
    /**
     * @Route("/", name="app_gameday_index")
     * @Route("/{season}/week/{week}", name="app_gameday_week")
     */
    public function indexAction($season = null, $week = null)
    {
        $weekService = $this->get('collegefootball.team.week');
        $weekResult  = $weekService->currentWeek($season, $week);
        $week        = $weekResult['week'];
        $season      = $weekResult['season'];
        $seasonWeeks = $weekResult['seasonWeeks'];

        $em         = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('AppBundle:Gameday');
        $gameday    = $repository->findOneByWeek($week);

        $pickemService = $this->get('collegefootball.app.pickem');
        $gamedayPicks  = $pickemService->gamedayWeekPicks($week);

        return $this->render('AppBundle:Gameday:index.html.twig', [
            'week'          => $week,
            'season'        => $season,
            'season_weeks'  => $seasonWeeks,
            'gameday'       => $gameday,
            'gameday_picks' => $gamedayPicks,
        ]);
    }

    /**
     * @Route("/{week}/add", name="app_gameday_add")
     * @Security("is_granted('ROLE_MANAGE')")
     */
    public function addAction(Week $week, Request $request)
    {
        $gameday = new Gameday();
        $gameday->setWeek($week);

        $form = $this->createForm(GamedayType::class, $gameday, [
            'week' => $week,
        ]);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($gameday);
            $em->flush();

            $this->addFlash('success', 'Gameday added');
            return $this->redirectToRoute('app_gameday_index');
        }

        return $this->render('AppBundle:Gameday:addEdit.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
