<?php

namespace CollegeFootball\TeamBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/team/{slug}/schedule")
 */
class ScheduleController extends Controller
{
    public function indexAction()
    {
        return $this->render('.html.twig');
    }
}
