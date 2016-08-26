<?php

namespace CollegeFootball\AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/prediction")
 */
class PredictionController extends Controller
{
    /**
     * @Route("/", name="collegefootball_prediction_index")
     */
    public function indexAction()
    {
        return $this->render('.html.twig');
    }


}
