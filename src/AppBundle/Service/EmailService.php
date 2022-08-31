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
    private $pickemService;
    private $weekService;

    public function __construct(EntityManager $em, Swift_Mailer $mailer, EngineInterface $templating, PickemService $pickemService, WeekService $weekService)
    {
        $this->em            = $em;
        $this->mailer        = $mailer;
        $this->templating    = $templating;
        $this->pickemService = $pickemService;
        $this->weekService   = $weekService;
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

        $week = $this->weekService->currentWeek()['week'];

        $repository = $this->em->getRepository('AppBundle:Game');
        $games      = $repository->findGamesByWeek($week, false, true);

        foreach ($people as $person) {
            $weekWinners = $this->pickemService->predictedWeekWinnersByPerson($person, $week);

            if (count($weekWinners) == count($games)) {
                // don't notify if they have aleady picked
                continue;
            }

            if ($person->getPhoneLink()) {
                $body = 'Reminder to complete your weekly college football pick\'em predictions at: elliscfb.com/person/pickem';
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
                    'week'               => $week,
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
        $email = new \SendGrid\Mail\Mail();
        $email->setFrom("matt@codellis.com");
        $email->addTo($to);

        if ($subject) {
            $email->setSubject($subject);
            $email->addContent("text/html", $body);
        } else {
            $email->addContent("text/plain", $body);
        }

        $sendgrid = new \SendGrid(getenv('SENDGRID_API_KEY'));

        try {
            $sendgrid->send($email);
        } catch (\Exception $e) {
            echo 'Caught exception: '. $e->getMessage() ."\n";
        }
    }

    private function sendNotificationSMTP($to, $body, $subject = null)
    {
        $message = \Swift_Message::newInstance()
            ->setFrom('noreply@elliscfb.com')
            ->setTo($to)
            ->setBody($body, 'text/html');

        if ($subject) {
            $message->setSubject($subject);
        }

        $this->mailer->send($message);
    }
}
