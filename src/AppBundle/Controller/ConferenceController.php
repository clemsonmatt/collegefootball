<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use AppBundle\Entity\Conference;
use AppBundle\Form\Type\ConferenceType;

/**
 * @Route("/conference")
 */
class ConferenceController extends Controller
{
    /**
     * @Route("/", name="app_conference_index")
     */
    public function indexAction()
    {
        $em          = $this->getDoctrine()->getManager();
        $repository  = $em->getRepository('AppBundle:Conference');
        $conferences = $repository->schoolsByDivision();

        return $this->render('AppBundle:Conference:index.html.twig', [
            'conferences' => $conferences,
        ]);
    }

    /**
     * @Route("/{slug}/show", name="app_conference_show")
     */
    public function showAction(Conference $conference)
    {
        return $this->render('AppBundle:Conference:show.html.twig', [
            'conference' => $conference,
        ]);
    }

    /**
     * @Route("/add", name="app_conference_add")
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
            return $this->redirectToRoute('app_conference_show', [
                'slug' => $conference->getSlug(),
            ]);
        }

        return $this->render('AppBundle:Conference:addEdit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{slug}/edit", name="app_conference_edit")
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
            return $this->redirectToRoute('app_conference_show', [
                'slug' => $conference->getSlug(),
            ]);
        }

        return $this->render('AppBundle:Conference:addEdit.html.twig', [
            'form'       => $form->createView(),
            'conference' => $conference,
        ]);
    }

    /**
     * @Route("/{slug}/remove", name="app_conference_remove")
     * @Security("is_granted('ROLE_MANAGE')")
     */
    public function removeAction(Conference $conference)
    {
        $em = $this->getDoctrine()->getManager();
        $em->remove($conference);
        $em->flush();

        $this->addFlash('warning', 'Conference removed');
        return $this->redirectToRoute('app_conference_index');
    }
}
