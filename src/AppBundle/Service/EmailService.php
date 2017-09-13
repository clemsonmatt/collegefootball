<?php

namespace AppBundle\Service;

use Doctrine\ORM\EntityManager;
use Swift_Mailer;
use Symfony\Component\Templating\EngineInterface;

class EmailService
{
    private $em;
    private $mailer;
    private $templating;

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
        $repository = $this->em->getRepository('AppBundle:Person');
        $people     = $repository->createQueryBuilder('p')
            ->where('p.email IS NOT NULL')
            ->andWhere('p.emailSubscription = 1 OR p.textSubscription = 1')
            ->getQuery()
            ->getResult();

        foreach ($people as $person) {
            if ($person->getPhoneLink()) {
                $body = 'Reminder to complete your weekly college football pick\'em predictions at: college-football.herokuapp.com';
                $this->sendNotification($person->getPhoneLink(), $body);
            } else {
                $correct           = $person->getPredictionWins();
                $totalPicks        = $correct + $person->getPredictionLosses();
                $pickemPredictions = 0;

                if ($totalPicks) {
                    $pickemPredictions = ($correct / ($correct + $person->getPredictionLosses())) * 100;
                }

                $template = $this->templating->render('AppBundle:Email:pickemReminder.html.twig', [
                    'person'             => $person,
                    'pickem_predictions' => $pickemPredictions,
                ]);

                $this->sendNotification($person->getEmail(), $template, 'College Football Pick\'em Remider');
            }
        }
    }

    /**
     * Send an email
     */
    private function sendNotification($to, $body, $subject = null)
    {
        $message = \Swift_Message::newInstance()
            ->setFrom('noreply@college-football.herokuapp.com')
            ->setTo($to)
            ->setBody($body);

        if ($subject) {
            $message->setSubject($subject);
        }

        $this->mailer->send($message);
    }
}
