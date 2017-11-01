<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

use AppBundle\Entity\Week;
use AppBundle\Entity\Team;

/**
 * @ORM\Entity()
 * @ORM\Table(name="ranking")
 */
class Ranking
{
    public function __toString()
    {
        return (string)$this->team;
    }

    public function isCurrentWeek()
    {
        $today = new \DateTime("now");

        $startDate = $this->week->getStartDate();
        $endDate   = $this->week->getEndDate();

        if ($startDate->format('U') <= $today->format('U') && $endDate->format('U') >= $today->format('U')) {
            return true;
        } elseif ($this->usePlayoffRanking() && ($startDate->modify('+3 days')->format('U') <= $today->format('U') && $endDate->modify('+3 days')->format('U') >= $today->format('U'))) {
            /* 3 day buffer */
            return true;
        }

        return false;
    }

    public function usePlayoffRanking()
    {
        return $this->week->getNumber() >= 10;
    }

    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(name="ap_rank", type="integer", nullable=true)
     */
    private $apRank;

    /**
     * @ORM\Column(name="coaches_poll_rank", type="integer", nullable=true)
     */
    private $coachesPollRank;

    /**
     * @ORM\Column(name="playoff_rank", type="integer", nullable=true)
     */
    private $playoffRank;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Week", inversedBy="rankings")
     * @ORM\JoinColumn(name="week_id", referencedColumnName="id")
     */
    private $week;

    /**
     * @ORM\ManyToOne(targetEntity="Team", inversedBy="rankings")
     * @ORM\JoinColumn(name="team_id", referencedColumnName="id")
     */
    private $team;


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
     * Set apRank
     *
     * @param integer $apRank
     * @return Ranking
     */
    public function setApRank($apRank)
    {
        $this->apRank = $apRank;

        return $this;
    }

    /**
     * Get apRank
     *
     * @return integer
     */
    public function getApRank()
    {
        return $this->apRank;
    }

    /**
     * Set coachesPollRank
     *
     * @param integer $coachesPollRank
     * @return Ranking
     */
    public function setCoachesPollRank($coachesPollRank)
    {
        $this->coachesPollRank = $coachesPollRank;

        return $this;
    }

    /**
     * Get playoffRank
     *
     * @return integer
     */
    public function getPlayoffRank()
    {
        return $this->playoffRank;
    }

    /**
     * Set playoffRank
     *
     * @param integer playoffRank
     * @return Ranking
     */
    public function setPlayoffRank($playoffRank)
    {
        $this->playoffRank = $playoffRank;

        return $this;
    }

    /**
     * Get coachesPollRank
     *
     * @return integer
     */
    public function getCoachesPollRank()
    {
        return $this->coachesPollRank;
    }

    /**
     * Set week
     *
     * @param Week $week
     * @return Ranking
     */
    public function setWeek(Week $week)
    {
        $this->week = $week;

        return $this;
    }

    /**
     * Get week
     *
     * @return Week
     */
    public function getWeek()
    {
        return $this->week;
    }

    /**
     * Set team
     *
     * @param Team $team
     * @return Ranking
     */
    public function setTeam(Team $team)
    {
        $this->team = $team;

        return $this;
    }

    /**
     * Get team
     *
     * @return Team
     */
    public function getTeam()
    {
        return $this->team;
    }
}
