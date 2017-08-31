<?php

namespace CollegeFootball\AppBundle\Service;

use Doctrine\ORM\EntityManager;
use JMS\DiExtraBundle\Annotation as DI;
use Swift_Mailer;
use Symfony\Component\Templating\EngineInterface;

/**
* @DI\Service("collegefootball.app.email")
*/
class EmailService
{
    private $em;
    private $mailer;
    private $templating;

    /**
     * @DI\InjectParams({
     *      "em"         = @DI\Inject("doctrine.orm.entity_manager"),
     *      "mailer"     = @DI\Inject("mailer"),
     *      "templating" = @DI\Inject("templating")
     *  })
     */
    public function __construct(EntityManager $em, Swift_Mailer $mailer, EngineInterface $templating)
    {
        $this->em         = $em;
        $this->mailer     = $mailer;
        $this->templating = $templating;
    }

    /**
     * Pickem reminder
     */
    public function sendPickemReminder()
    {
        $repository = $this->em->getRepository('CollegeFootballAppBundle:Person');
        $people     = $repository->createQueryBuilder('p')
            ->where('p.email IS NOT NULL')
            // ->andWhere("p.firstName = 'Matt'")
            ->getQuery()
            ->getResult();

        foreach ($people as $person) {
            $correct           = $person->getPredictionWins();
            $totalPicks        = $correct + $person->getPredictionLosses();
            $pickemPredictions = 0;

            if ($totalPicks) {
                $pickemPredictions = ($correct / ($correct + $person->getPredictionLosses())) * 100;
            }

            $template = $this->templating->render('CollegeFootballAppBundle:Email:pickemReminder.html.twig', [
                'person'             => $person,
                'pickem_predictions' => $pickemPredictions,
            ]);

            $this->sendEmail('College Football Pick\'em Remider', $person->getEmail(), $template);
        }
    }

    /**
     * Send an email
     */
    private function sendEmail($subject, $to, $template)
    {
        $message = \Swift_Message::newInstance()
            ->setSubject($subject)
            ->setFrom('noreply@college-football.herokuapp.com')
            ->setTo($to)
            ->setBody($template, 'text/html');

        $this->mailer->send($message);
    }
}
