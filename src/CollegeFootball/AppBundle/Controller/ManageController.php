<?php

namespace CollegeFootball\AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/manage")
 * @Security("is_granted('ROLE_MANAGE')")
 */
class ManageController extends Controller
{
    /**
     * @Route("/people/", name="collegefootball_manage_people")
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
}
