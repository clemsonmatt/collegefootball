<?php

namespace CollegeFootball\AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use CollegeFootball\AppBundle\Entity\Gameday;
use CollegeFootball\AppBundle\Entity\Week;
use CollegeFootball\AppBundle\Form\Type\GamedayType;

/**
 * @Route("/gameday")
 */
class GamedayController extends Controller
{
    /**
     * @Route("/", name="collegefootball_gameday_index")
     */
    public function indexAction()
    {
        $weekService = $this->get('collegefootball.team.week');
        $weekResult  = $weekService->currentWeek();
        $week        = $weekResult['week'];

        $em         = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('CollegeFootballAppBundle:Gameday');
        $gameday    = $repository->findOneByWeek($week);

        return $this->render('CollegeFootballAppBundle:Gameday:index.html.twig', [
            'week'    => $week,
            'gameday' => $gameday,
        ]);
    }

    /**
     * @Route("/{week}/add", name="collegefootball_gameday_add")
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
            return $this->redirectToRoute('collegefootball_gameday_index');
        }

        return $this->render('CollegeFootballAppBundle:Gameday:addEdit.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
