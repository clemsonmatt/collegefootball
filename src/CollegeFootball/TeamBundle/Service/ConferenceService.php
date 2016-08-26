<?php

namespace CollegeFootball\TeamBundle\Service;

use Doctrine\ORM\EntityManager;
use JMS\DiExtraBundle\Annotation as DI;

use CollegeFootball\TeamBundle\Entity\Conference;
use CollegeFootball\TeamBundle\Entity\Team;

/**
* @DI\Service("collegefootball.team.conference")
*/
class ConferenceService
{
    private $em;

    /**
     * @DI\InjectParams({
     *      "em" = @DI\Inject("doctrine.orm.entity_manager")
     *  })
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function teamRankInConference(Conference $conference)
    {
        $subConferences = $conference->teamsBySubConference();

        $rankedTeams = [];

        /* rank the teams with win/loss */
        foreach ($subConferences as $subConference => $subConferenceTeams) {
            if (is_array($subConferenceTeams)) {
                foreach ($subConferenceTeams as $team) {
                    $rankedTeams = $this->getTeamRanking($team, $rankedTeams);
                }
            } else {
                $rankedTeams = $this->getTeamRanking($subConferenceTeams, $rankedTeams);
            }
        }

        krsort($rankedTeams);

        return $rankedTeams;
    }

    private function getTeamRanking(Team $team, $rankedTeams)
    {
        $gamesWon = count($team->getWonGames());

        $repository  = $this->em->getRepository('CollegeFootballTeamBundle:Game');
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
