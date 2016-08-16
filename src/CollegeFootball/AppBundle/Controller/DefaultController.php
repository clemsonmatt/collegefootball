<?php

namespace CollegeFootball\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="collegefootball_app_index")
     */
    public function indexAction()
    {
        return $this->render('CollegeFootballAppBundle:Default:index.html.twig');
    }
}
