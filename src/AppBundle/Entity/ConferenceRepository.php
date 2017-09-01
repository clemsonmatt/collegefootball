<?php

namespace AppBundle\Entity;

use Doctrine\ORM\EntityRepository;

class ConferenceRepository extends EntityRepository
{
    public function schoolsByDivision() {
        $allConferences = $this->findAll();

        $conferences = [
            'fbs' => [],
            'fcs' => [],
        ];

        foreach ($allConferences as $conference) {
            $divisionShort  = strpos($conference->getDivision(), 'FBS') !== false ? 'fbs' : 'fcs';
            $conferenceName = $conference->getNameShort();

            $conferences[$divisionShort][$conferenceName]['slug'] = $conference->getSlug();

            if (count($conference->getSubConferences())) {
                foreach ($conference->getTeams() as $team) {
                    $conferences[$divisionShort][$conferenceName]['subConference'][$team->getSubConference()]['teams'][$team->getSlug()] = $team->getName();
                }
            } else {
                foreach ($conference->getTeams() as $team) {
                    $conferences[$divisionShort][$conferenceName]['teams'][$team->getSlug()] = $team->getName();
                }
            }
        }

        return $conferences;
    }
}
