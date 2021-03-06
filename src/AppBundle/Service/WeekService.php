<?php

namespace AppBundle\Service;

use Doctrine\ORM\EntityManager;

class WeekService
{
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function currentWeek($season = null, $week = null, $includePreseason = false)
    {
        if (! $season) {
            $season = 2020;
        }

        $repository  = $this->em->getRepository('AppBundle:Week');
        $seasonWeeks = $repository->createQueryBuilder('w')
            ->where('w.season = :season');

        if (! $includePreseason) {
            $seasonWeeks = $seasonWeeks->andWhere('w.number > 0');
        }

        $seasonWeeks = $seasonWeeks->orderBy('w.endDate', 'ASC')
            ->setParameter('season', $season)
            ->getQuery()
            ->getResult();

        if ($week) {
            $week = $repository->findOneBy([
                'season' => $season,
                'number' => $week,
            ], [
                'endDate' => 'ASC'
            ]);
        } else {
            $week = $seasonWeeks[0];

            $today = new \DateTime("now");

            foreach ($seasonWeeks as $singleWeek) {
                if ($singleWeek->getEndDate()->format('U') > $today->format('U')) {
                    $week = $singleWeek;
                    break;
                }
            }
        }

        return [
            'week'        => $week,
            'season'      => $season,
            'seasonWeeks' => $seasonWeeks,
        ];
    }
}
