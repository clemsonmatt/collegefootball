<?php

namespace CollegeFootball\AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use CollegeFootball\AppBundle\Traits\TimestampableTrait;

/**
 * @ORM\Entity()
 * @ORM\Table(name="news")
 */
class News
{
    use TimestampableTrait;

    public function __toString()
    {
        return $this->title;
    }

    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", name="title", length=255)
     */
    private $title;

    /**
     * @ORM\Column(type="text", name="description")
     */
    private $description;

    /**
     * @ORM\Column(type="string", name="link", length=255)
     */
    private $link;

    /**
     * @ORM\Column(type="datetime", name="date")
     */
    private $date;

    /**
     * @ORM\Column(type="string", name="espn_guid")
     */
    private $espnGuid;


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
     * Set title
     *
     * @param string $title
     * @return News
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set description
     *
     * @param text $description
     * @return News
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return text
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set link
     *
     * @param string $link
     * @return News
     */
    public function setLink($link)
    {
        $this->link = $link;

        return $this;
    }

    /**
     * Get link
     *
     * @return string
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     * Set date
     *
     * @param DateTime $date
     * @return News
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date
     *
     * @return DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set espnGuid
     *
     * @param string $espnGuid
     * @return News
     */
    public function setEspnGuid($espnGuid)
    {
        $this->espnGuid = $espnGuid;

        return $this;
    }

    /**
     * Get espnGuid
     *
     * @return string
     */
    public function getEspnGuid()
    {
        return $this->espnGuid;
    }
}
