<?php

namespace AppBundle\Service;

use Doctrine\ORM\EntityManager;

use AppBundle\Entity\Conference;
use AppBundle\Entity\Team;

class ConferenceService
{
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function teamRankInConference(Conference $conference, $bySubConference = true)
    {
        $subConferences = $conference->teamsBySubConference();

        $rankedTeams      = [];
        $hasSubConference = false;

        /* rank the teams with win/loss */
        foreach ($subConferences as $subConference => $subConferenceTeams) {
            if (is_array($subConferenceTeams)) {
                $hasSubConference = true;

                foreach ($subConferenceTeams as $team) {
                    $rankedTeams = $this->getTeamRanking($team, $rankedTeams);
                }
            } else {
                $rankedTeams = $this->getTeamRanking($subConferenceTeams, $rankedTeams);
            }
        }

        krsort($rankedTeams);

        if (! $bySubConference) {
            return $rankedTeams;
        }

        /* now if there are sub-conferences, sort by those */
        $rankedBySubConference = [];
        $usedTeams             = [];

        foreach ($rankedTeams as $points => $rankedPointTeams) {
            foreach ($rankedPointTeams as $rankedTeam) {
                $rankedBySubConference[$rankedTeam['subConference']][] = $rankedTeam;
            }
        }

        return $rankedBySubConference;
    }

    private function getTeamRanking(Team $team, $rankedTeams)
    {
        $gamesWon = count($team->getWonGames());

        $repository  = $this->em->getRepository('AppBundle:Game');
        $gamesPlayed = $repository->createQueryBuilder('g')
            ->where('g.winningTeam IS NOT NULL')
            ->andWhere('g.homeTeam = :team OR g.awayTeam = :team')
            ->setParameter('team', $team)
            ->getQuery()
            ->getResult();

        $gamesLost  = count($gamesPlayed) - $gamesWon;
        $rankPoints = $gamesWon - $gamesLost;

        /* find the conference ranking */
        $conferenceGamesPlayed = $repository->createQueryBuilder('g')
            ->join('g.homeTeam', 'ht')
            ->join('g.awayTeam', 'at')
            ->join('ht.conference', 'htc')
            ->join('at.conference', 'atc')
            ->where('g.winningTeam IS NOT NULL')
            ->andWhere('g.homeTeam = :team OR g.awayTeam = :team')
            ->andWhere('htc = :conference AND atc = :conference')
            ->setParameter('team', $team)
            ->setParameter('conference', $team->getConference())
            ->getQuery()
            ->getResult();

        $conferenceGamesWon = 0;
        foreach ($conferenceGamesPlayed as $conferenceGame) {
            if ($conferenceGame->getWinningTeam() == $team) {
                $conferenceGamesWon++;
            }
        }

        $rankPoints += $conferenceGamesWon;

        $rankedTeams[$rankPoints][] = [
            'subConference'    => $team->getSubConference(),
            'team'             => $team,
            'gamesWon'         => $gamesWon,
            'gamesPlayed'      => count($gamesPlayed),
            'conferenceWon'    => $conferenceGamesWon,
            'conferencePlayed' => count($conferenceGamesPlayed),
        ];

        return $rankedTeams;
    }
}
