<?php

namespace CollegeFootball\TeamBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

use CollegeFootball\AppBundle\Traits\TimestampableTrait;

/**
 * @ORM\Entity()
 * @ORM\Table(name="conference")
 */
class Conference
{
    use TimestampableTrait;

    public function __toString()
    {
        return $this->nameShort;
    }

    public function teamsBySubConference()
    {
        $teamsBySubConference = [];

        if (count($this->subConferences) == 0) {
            return $this->teams;
        }

        foreach ($this->subConferences as $subConference) {
            foreach ($this->teams as $team) {
                if ($team->getSubConference() == $subConference) {
                    $teamsBySubConference[$subConference][] = $team;
                }
            }
        }

        return $teamsBySubConference;
    }

    public function teamsInSubConference($subConference)
    {
        $teamsInSubConference = [];

        $allTeams = $this->teamsBySubConference();

        if (array_key_exists($subConference, $allTeams)) {
            return $allTeams[$subConference];
        }

        return null;
    }

    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(name="name_short", type="string", length=255)
     */
    private $nameShort;

    /**
     * @ORM\Column(name="slug", type="string", length=255, unique=true)
     */
    protected $slug;

    /**
     * @ORM\Column(name="division", type="string", length=255)
     */
    private $division;

    /**
     * @ORM\Column(name="sub_conferences", type="array", nullable=true)
     */
    private $subConferences;

    /**
     * @ORM\OneToMany(targetEntity="Team", mappedBy="conference")
     */
    private $teams;


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
     * Set name
     *
     * @param string $name
     * @return Conference
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set nameShort
     *
     * @param string $nameShort
     * @return Conference
     */
    public function setNameShort($nameShort)
    {
        $this->nameShort = $nameShort;

        $this->slug = strtolower($nameShort);
        $this->slug = str_replace(" ", "-", $this->slug);

        return $this;
    }

    /**
     * Get nameShort
     *
     * @return string
     */
    public function getNameShort()
    {
        return $this->nameShort;
    }

    /**
     * Set division
     *
     * @param string $division
     * @return Conference
     */
    public function setDivision($division)
    {
        $this->division = $division;

        return $this;
    }

    /**
     * Get division
     *
     * @return string
     */
    public function getDivision()
    {
        return $this->division;
    }

    /**
     * Set subConferences
     *
     * @param array $subConferences
     * @return Conference
     */
    public function setSubConferences($subConferences)
    {
        $this->subConferences = $subConferences;

        return $this;
    }

    /**
     * Get subConferences
     *
     * @return array
     */
    public function getSubConferences()
    {
        return $this->subConferences;
    }

    /**
     * Get slug
     *
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * Get teams
     *
     * @return string
     */
    public function getTeams()
    {
        return $this->teams;
    }
}
