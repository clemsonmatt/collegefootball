<?php

namespace AppBundle\Service;

use Doctrine\ORM\EntityManager;

use AppBundle\Entity\Game;
use AppBundle\Entity\Week;

class WeeklyScoresService
{
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function importScores(Week $week)
    {
        $games = $this->getGames($week);

        foreach ($games as $game) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_URL, 'https://www.espn.com/college-football/matchup?gameId='.$game->getEspnId());

            $response = curl_exec($ch);
            curl_close($ch);

            $stats = $this->getStats($response);

            $game->setStats($stats);
            $game->setWinnerFromStats();
        }

        $this->em->flush();
    }

    public function updateData(Week $week)
    {
        $games = $this->getGames($week);

        foreach ($games as $game) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_URL, 'https://www.espn.com/college-football/game/_/gameId/'.$game->getEspnId());

            $response = curl_exec($ch);
            curl_close($ch);

            // update spread
            list($spread, $predictedWinner) = $this->getSpread($response);
            $game->setSpread($spread);
            $game->setPredictedWinner($predictedWinner);

            // update time
            $game->setTime($this->getTime($response));
        }

        $this->em->flush();
    }

    private function getGames($week)
    {
        $repository = $this->em->getRepository(Game::class);
        $games      = $repository->createQueryBuilder('g')
            ->where('g.date >= :startDate')
            ->andWhere('g.date <= :endDate')
            ->andWhere('g.espnId IS NOT NULL')
            ->andWhere('g.canceled = 0')
            ->setParameter('startDate', $week->getStartDate())
            ->setParameter('endDate', $week->getEndDate())
            ->getQuery()
            ->getResult();

        return $games;
    }

    private function getStats($response)
    {
        // first check to see if game has any stats
        if (! $this->hasGameStats($response)) {
            return null;
        }

        $defaultStats = [
            'pointsFinal'        => null,
            'pointsFirst'        => null,
            'pointsSecond'       => null,
            'pointsThird'        => null,
            'pointsFourth'       => null,
            'ot'                 => null,
            'rushingYards'       => null,
            'rushingAttempts'    => null,
            'passingYards'       => null,
            'passingAttempts'    => null,
            'passingCompletions' => null,
            'totalOffenseYards'  => null,
            'turnoverCount'      => null,
            'penaltyYards'       => null,
        ];

        $stats = [
            'homeStats' => $defaultStats,
            'awayStats' => $defaultStats,
        ];

        // stats
        $stats['homeStats'] = $this->getTeamStats($response, 'home') + $stats['homeStats'];
        $stats['awayStats'] = $this->getTeamStats($response, 'away') + $stats['awayStats'];

        // linescores
        $linescore = $this->getStringBetween($response, '<table id="linescore" class="miniTable">', '</table>');

        $linescore = explode('<tr>', $linescore);
        $linescore = str_replace(['</tr>', '</tbody>'], '', $linescore);

        $stats['homeStats'] = $this->getLineScores($linescore[3]) + $stats['homeStats'];
        $stats['awayStats'] = $this->getLineScores($linescore[2]) + $stats['awayStats'];

        return $stats;
    }

    private function hasGameStats($response)
    {
        $matchupContent = $this->getStringBetween($response, '<div id="gamepackage-matchup" data-module="matchup"  data-sport="football">', '</div>');

        return strpos($matchupContent, 'No Team Stats Available') === false;
    }

    private function getStringBetween($string, $start, $end)
    {
        $ini = strpos($string, $start);

        if ($ini == 0) {
            return '';
        }

        $ini += strlen($start);
        $len = strpos($string, $end, $ini) - $ini;

        return substr($string, $ini, $len);
    }

    private function getLineScores($teamScore)
    {
        $qtrMap = [
            'final' => 'pointsFinal',
            1       => 'pointsFirst',
            2       => 'pointsSecond',
            3       => 'pointsThird',
            4       => 'pointsFourth',
            5       => 'ot',
        ];

        $scores = [
            'pointsFinal'  => null,
            'pointsFirst'  => null,
            'pointsSecond' => null,
            'pointsThird'  => null,
            'pointsFourth' => null,
            'ot'           => null,
        ];

        $teamScores = explode('<td', $teamScore);
        foreach ($teamScores as $quarter => $teamQtrScore) {
            if ($quarter > 1) {
                if (strpos($teamQtrScore, 'final-score') !== false) {
                    $quarter = 'final';
                } else {
                    $quarter--;
                }

                $scores[$qtrMap[$quarter]] = str_replace(['</td>', '>', ' class="final-score"'], '', $teamQtrScore);
            }
        }

        return $scores;
    }

    private function getTeamStats($response, $homeAway)
    {
        $statIndex = 2;
        if ($homeAway == 'home') {
            $statIndex = 3;
        }

        $stats = [
            'totalOffenseYards'  => $this->getSingleStat($this->getStringBetween($response, '<tr class="highlight" data-stat-attr="totalYards">', '</tr>'), $statIndex),
            'rushingYards'       => $this->getSingleStat($this->getStringBetween($response, '<tr class="highlight" data-stat-attr="rushingYards">', '</tr>'), $statIndex),
            'rushingAttempts'    => $this->getSingleStat($this->getStringBetween($response, '<tr class="indent" data-stat-attr="rushingAttempts">', '</tr>'), $statIndex),
            'passingYards'       => $this->getSingleStat($this->getStringBetween($response, '<tr class="highlight" data-stat-attr="netPassingYards">', '</tr>'), $statIndex),
            'passingAttempts'    => $this->getSingleStat($this->getStringBetween($response, '<tr class="indent" data-stat-attr="completionAttempts">', '</tr>'), $statIndex, 1),
            'passingCompletions' => $this->getSingleStat($this->getStringBetween($response, '<tr class="indent" data-stat-attr="completionAttempts">', '</tr>'), $statIndex, 0),
            'turnoverCount'      => $this->getSingleStat($this->getStringBetween($response, '<tr class="highlight" data-stat-attr="turnovers">', '</tr>'), $statIndex),
            'penaltyYards'       => $this->getSingleStat($this->getStringBetween($response, '<tr class="highlight" data-stat-attr="totalPenaltiesYards">', '</tr>'), $statIndex, 1),
        ];

        return $stats;
    }

    private function getSingleStat($response, $statIndex, $subIndex = null)
    {
        $response = explode('<td>', $response);
        $response = str_replace('</td>', '', $response);

        $stat = trim($response[$statIndex]);

        if ($subIndex === null) {
            return $stat;
        }

        $stat = explode('-', $stat);

        return $stat[$subIndex];
    }

    private function getSpread($response)
    {
        $spread = null;
        $predictedWinner = null;

        $pickcenterData = $this->getStringBetween($response, '<table class="mediumTable">', '</table>');
        $awayData = $this->getStringBetween($pickcenterData, '<tr class="awayteam">', '</tr>');
        $awaySpread = $this->getStringBetween($awayData, '<td class="score">', '</td>');

        $homeData = $this->getStringBetween($pickcenterData, '<tr class="hometeam">', '</tr>');
        $homeSpread = $this->getStringBetween($homeData, '<td class="score">', '</td>');

        if (count($awaySpread) && $awaySpread < 0) {
            $spread = str_replace('-', '', $awaySpread);
            $predictedWinner = 'Away';
        } elseif (count($homeSpread) && $homeSpread < 0) {
            $spread = str_replace('-', '', $homeSpread);
            $predictedWinner = 'Home';
        }

        return [$spread, $predictedWinner];
    }

    private function getTime($response)
    {
        $timeString = $this->getStringBetween($response, '<span data-date="', '"');

        if (count($timeString)) {
            $dateTime = new \DateTime($timeString);
            return $dateTime->setTimezone(new \DateTimeZone('America/New_York'))->format('h:i A');
        }

        return null;
    }
}
