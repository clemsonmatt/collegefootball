<?php

namespace CollegeFootball\AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

use CollegeFootball\AppBundle\Entity\Week;
use CollegeFootball\TeamBundle\Entity\Game;

/**
 * @ORM\Entity()
 * @ORM\Table(name="gameday")
 */
class Gameday
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\OneToOne(targetEntity="Week")
     * @ORM\JoinColumn(name="week_id", referencedColumnName="id")
     */
    private $week;

    /**
     * @ORM\OneToOne(targetEntity="CollegeFootball\TeamBundle\Entity\Game")
     * @ORM\JoinColumn(name="game_id", referencedColumnName="id")
     */
    private $game;


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
     * Set location
     *
     * @param string $location
     * @return Gameday
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
     * Set week
     *
     * @param Week $week
     * @return Gameday
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
     * Set game
     *
     * @param Game $game
     * @return Gameday
     */
    public function setGame(Game $game)
    {
        $this->game = $game;

        return $this;
    }

    /**
     * Get game
     *
     * @return Game
     */
    public function getGame()
    {
        return $this->game;
    }
}
