<?php

namespace CollegeFootball\TeamBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="CollegeFootball\TeamBundle\Entity\GameRepository")
 * @ORM\Table(name="game")
 */
class Game
{
    public function __toString()
    {
        return $this->awayTeam.' @ '.$this->homeTeam;
    }

    public function canPick()
    {
        if ($this->winningTeam) {
            return false;
        }

        $now  = new \DateTime();
        $date = $this->date->format('Y-m-d');
        $time = new \DateTime($this->time);

        if ($date < $now->format('Y-m-d') || ($date == $now->modify('-3 hour')->format('Y-m-d') && $time->format('U') < $now->modify('-1 hour')->format('U'))) {
            return false;
        }

        return true;
    }

    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(name="date", type="date")
     */
    private $date;

    /**
     * @ORM\Column(name="time", type="string", nullable=true)
     */
     private $time;

    /**
     * @ORM\Column(name="season", type="integer")
     */
    private $season;

    /**
     * @ORM\ManyToOne(targetEntity="Team", inversedBy="homeGames")
     * @ORM\JoinColumn(name="home_team_id", referencedColumnName="id")
     */
    private $homeTeam;

    /**
     * @ORM\ManyToOne(targetEntity="Team", inversedBy="awayGames")
     * @ORM\JoinColumn(name="away_team_id", referencedColumnName="id")
     */
    private $awayTeam;

    /**
     * @ORM\ManyToOne(targetEntity="Team", inversedBy="wonGames")
     * @ORM\JoinColumn(name="winning_team_id", referencedColumnName="id", nullable=true)
     */
    private $winningTeam;

    /**
     * @ORM\Column(name="location", type="string", length=255)
     */
    private $location;

    /**
     * @ORM\Column(name="spread", type="decimal", precision=3, scale=1, nullable=true)
     */
    private $spread;

    /**
     * @ORM\Column(name="predicted_winner", type="string", length=255, nullable=true)
     */
    private $predictedWinner;

    /**
     * @ORM\Column(name="stats", type="array", nullable=true)
     */
    private $stats;

    /**
     * @ORM\Column(name="winning_chance", type="array", nullable=true)
     */
    private $winningChance;

    /**
     * @ORM\Column(name="conference_championship", type="boolean")
     */
    private $conferenceChampionship = false;

    /**
     * @ORM\Column(name="bowl_name", type="string", length=255, nullable=true)
     */
    private $bowlName;


    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set date
     *
     * @param Date $date
     * @return Game
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date
     *
     * @return Date
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set time
     *
     * @param time $time
     * @return Game
     */
    public function setTime($time)
    {
        $this->time = $time;

        return $this;
    }

    /**
     * Get time
     *
     * @return time
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * Set season
     *
     * @param integer $season
     * @return Game
     */
    public function setSeason($season)
    {
        $this->season = $season;

        return $this;
    }

    /**
     * Get season
     *
     * @return integer
     */
    public function getSeason()
    {
        return $this->season;
    }

    /**
     * Set homeTeam
     *
     * @param Team $homeTeam
     * @return Game
     */
    public function setHomeTeam($homeTeam)
    {
        $this->homeTeam = $homeTeam;

        return $this;
    }

    /**
     * Get homeTeam
     *
     * @return Team
     */
    public function getHomeTeam()
    {
        return $this->homeTeam;
    }

    /**
     * Set awayTeam
     *
     * @param Team $awayTeam
     * @return Game
     */
    public function setAwayTeam($awayTeam)
    {
        $this->awayTeam = $awayTeam;

        return $this;
    }

    /**
     * Get awayTeam
     *
     * @return Team
     */
    public function getAwayTeam()
    {
        return $this->awayTeam;
    }

    /**
     * Set winningTeam
     *
     * @param Team $winningTeam
     * @return Game
     */
    public function setWinningTeam($winningTeam)
    {
        $this->winningTeam = $winningTeam;

        return $this;
    }

    /**
     * Get winningTeam
     *
     * @return Team
     */
    public function getWinningTeam()
    {
        return $this->winningTeam;
    }

    /**
     * Set location
     *
     * @param string $location
     * @return Game
     */
    public function setLocation($location)
    {
        $this->location = $location;

        return $this;
    }

    /**
     * Get location
     *
     * @return string
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * Set spread
     *
     * @param integer $spread
     * @return Game
     */
    public function setSpread($spread)
    {
        $this->spread = $spread;

        return $this;
    }

    /**
     * Get spread
     *
     * @return integer
     */
    public function getSpread()
    {
        return $this->spread;
    }

    /**
     * Set predictedWinner
     *
     * @param string $predictedWinner
     * @return Game
     */
    public function setPredictedWinner($predictedWinner)
    {
        $this->predictedWinner = $predictedWinner;

        return $this;
    }

    /**
     * Get predictedWinner
     *
     * @return string
     */
    public function getPredictedWinner()
    {
        return $this->predictedWinner;
    }

    /**
     * Set stats
     *
     * @param array $stats
     * @return Game
     */
    public function setStats($stats)
    {
        $this->stats = $stats;

        return $this;
    }

    /**
     * Get stats
     *
     * @return array
     */
    public function getStats()
    {
        return $this->stats;
    }

    /**
     * Set winningChance
     *
     * @param array $winningChance
     * @return Game
     */
    public function setWinningChance($winningChance)
    {
        $winningChance['home'] = (int)($winningChance['home'] * 100);
        $winningChance['away'] = (int)($winningChance['away'] * 100);

        $this->winningChance = $winningChance;

        return $this;
    }

    /**
     * Get winningChance
     *
     * @return array
     */
    public function getWinningChance()
    {
        if (! $this->winningChance) {
            return null;
        }

        $winningChance['home'] = (float)($this->winningChance['home'] / 100);
        $winningChance['away'] = (float)($this->winningChance['away'] / 100);

        return $winningChance;
    }

    /**
     * Set conferenceChampionship
     *
     * @param boolean conferenceChampionship
     * @return Game
     */
    public function setConferenceChampionship($conferenceChampionship)
    {
        $this->conferenceChampionship = $conferenceChampionship;

        return $this;
    }

    /**
     * Get conferenceChampionship
     *
     * @return boolean
     */
    public function isConferenceChampionship()
    {
        return $this->conferenceChampionship;
    }

    /**
     * Set bowlName
     *
     * @param string $bowlName
     * @return Game
     */
    public function setBowlName($bowlName)
    {
        $this->bowlName = $bowlName;

        return $this;
    }

    /**
     * Get bowlName
     *
     * @return string
     */
    public function getBowlName()
    {
        return $this->bowlName;
    }
}
