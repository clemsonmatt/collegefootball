<?php

namespace CollegeFootball\AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use CollegeFootball\AppBundle\Entity\Person;
use CollegeFootball\AppBundle\Form\Type\PersonType;

/**
 * @Route("/manage")
 * @Security("is_granted('ROLE_MANAGE')")
 */
class ManageController extends Controller
{
    /**
     * @Route("/people", name="collegefootball_manage_people")
     */
    public function indexAction()
    {
        $em         = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('CollegeFootballAppBundle:Person');
        $people     = $repository->findBy([], ['lastName' => 'ASC']);

        return $this->render('CollegeFootballAppBundle:Manage:people.html.twig', [
            'people' => $people,
        ]);
    }

    /**
     * @Route("/people/add", name="collegefootball_manage_people_add")
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

            return $this->redirectToRoute('collegefootball_manage_people');
        }

        return $this->render('CollegeFootballAppBundle:Manage:addPerson.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/pickem-reminder-email", name="collegefootball_manage_pickem_reminder_email")
     */
    public function pickemReminderEmailAction()
    {
        $emailService = $this->get('collegefootball.app.email');
        $emailService->sendPickemReminder();

        $this->addFlash('success', 'Pickem reminder sent');
        return $this->redirectToRoute('collegefootball_manage_people');
    }
}
