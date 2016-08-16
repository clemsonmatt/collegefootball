<?php

namespace CollegeFootball\TeamBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

use CollegeFootball\AppBundle\Entity\Week;
use CollegeFootball\TeamBundle\Entity\Team;

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
     * @ORM\Column(name="conference_rank", type="integer", nullable=true)
     */
    private $conferenceRank;

    /**
     * @ORM\Column(name="sub_conference_rank", type="integer", nullable=true)
     */
    private $subConferenceRank;

    /**
     * @ORM\ManyToOne(targetEntity="CollegeFootball\AppBundle\Entity\Week", inversedBy="rankings")
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
     * Get coachesPollRank
     *
     * @return integer
     */
    public function getCoachesPollRank()
    {
        return $this->coachesPollRank;
    }

    /**
     * Set conferenceRank
     *
     * @param integer $conferenceRank
     * @return Ranking
     */
    public function setConferenceRank($conferenceRank)
    {
        $this->conferenceRank = $conferenceRank;

        return $this;
    }

    /**
     * Get conferenceRank
     *
     * @return integer
     */
    public function getConferenceRank()
    {
        return $this->conferenceRank;
    }

    /**
     * Set subConferenceRank
     *
     * @param integer $subConferenceRank
     * @return Ranking
     */
    public function setSubConferenceRank($subConferenceRank)
    {
        $this->subConferenceRank = $subConferenceRank;

        return $this;
    }

    /**
     * Get subConferenceRank
     *
     * @return integer
     */
    public function getSubConferenceRank()
    {
        return $this->subConferenceRank;
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
