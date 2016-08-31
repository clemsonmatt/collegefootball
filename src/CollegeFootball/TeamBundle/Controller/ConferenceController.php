<?php

namespace CollegeFootball\TeamBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use CollegeFootball\TeamBundle\Entity\Conference;
use CollegeFootball\TeamBundle\Form\Type\ConferenceType;

/**
 * @Route("/conference")
 */
class ConferenceController extends Controller
{
    /**
     * @Route("/", name="collegefootball_team_conference_index")
     */
    public function indexAction()
    {
        $em             = $this->getDoctrine()->getManager();
        $repository     = $em->getRepository('CollegeFootballTeamBundle:Conference');
        $fbsConferences = $repository->findBy(['division' => 'FBS (Division I-A Conferences)'], ['name' => 'asc']);
        $fcsConferences = $repository->findBy(['division' => 'FCS (Division I-AA Conferences)'], ['name' => 'asc']);

        return $this->render('CollegeFootballTeamBundle:Conference:index.html.twig', [
            'fbs_conferences' => $fbsConferences,
            'fcs_conferences' => $fcsConferences
        ]);
    }

    /**
     * @Route("/{slug}/show", name="collegefootball_team_conference_show")
     */
    public function showAction(Conference $conference)
    {
        return $this->render('CollegeFootballTeamBundle:Conference:show.html.twig', [
            'conference' => $conference,
        ]);
    }

    /**
     * @Route("/add", name="collegefootball_team_conference_add")
     * @Security("is_granted('ROLE_MANAGE')")
     */
    public function addAction(Request $request)
    {
        $conference = new Conference();
        $form       = $this->createForm(ConferenceType::class, $conference);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($conference);
            $em->flush();

            $this->addFlash('success', 'Conference added');
            return $this->redirectToRoute('collegefootball_team_conference_show', [
                'slug' => $conference->getSlug(),
            ]);
        }

        return $this->render('CollegeFootballTeamBundle:Conference:addEdit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{slug}/edit", name="collegefootball_team_conference_edit")
     * @Security("is_granted('ROLE_MANAGE')")
     */
    public function editAction(Conference $conference, Request $request)
    {
        $form = $this->createForm(ConferenceType::class, $conference);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->flush();

            $this->addFlash('success', 'Conference saved');
            return $this->redirectToRoute('collegefootball_team_conference_show', [
                'slug' => $conference->getSlug(),
            ]);
        }

        return $this->render('CollegeFootballTeamBundle:Conference:addEdit.html.twig', [
            'form'       => $form->createView(),
            'conference' => $conference,
        ]);
    }

    /**
     * @Route("/{slug}/remove", name="collegefootball_team_conference_remove")
     * @Security("is_granted('ROLE_MANAGE')")
     */
    public function removeAction(Conference $conference)
    {
        $em = $this->getDoctrine()->getManager();
        $em->remove($conference);
        $em->flush();

        $this->addFlash('warning', 'Conference removed');
        return $this->redirectToRoute('collegefootball_team_conference_index');
    }
}
