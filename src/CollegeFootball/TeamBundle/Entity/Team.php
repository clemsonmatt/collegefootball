<?php

namespace CollegeFootball\TeamBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

use CollegeFootball\AppBundle\Traits\TimestampableTrait;

/**
 * @ORM\Entity()
 * @ORM\Table(name="team")
 */
class Team
{
    use TimestampableTrait;

    public function __construct()
    {
        $this->homeGames = new ArrayCollection();
        $this->awayGames = new ArrayCollection();
        $this->wonGames  = new ArrayCollection();
        $this->rankings  = new ArrayCollection();
    }

    public function __toString()
    {
        if ($this->currentRanking()) {
            return '#'.$this->currentRanking()->getApRank().' '.$this->name;
        }

        return $this->name;
    }

    public static function getStatesList()
    {
        return [
            'AL' => 'Alabama',
            'AK' => 'Alaska',
            'AZ' => 'Arizona',
            'AR' => 'Arkansas',
            'CA' => 'California',
            'CO' => 'Colorado',
            'CT' => 'Connecticut',
            'DE' => 'Delaware',
            'DC' => 'District of Columbia',
            'FL' => 'Florida',
            'GA' => 'Georgia',
            'HI' => 'Hawaii',
            'ID' => 'Idaho',
            'IL' => 'Illinois',
            'IN' => 'Indiana',
            'IA' => 'Iowa',
            'KS' => 'Kansas',
            'KY' => 'Kentucky',
            'LA' => 'Louisiana',
            'ME' => 'Maine',
            'MD' => 'Maryland',
            'MA' => 'Massachusetts',
            'MI' => 'Michigan',
            'MN' => 'Minnesota',
            'MS' => 'Mississippi',
            'MO' => 'Missouri',
            'MT' => 'Montana',
            'NE' => 'Nebraska',
            'NV' => 'Nevada',
            'NH' => 'New Hampshire',
            'NJ' => 'New Jersey',
            'NM' => 'New Mexico',
            'NY' => 'New York',
            'NC' => 'North Carolina',
            'ND' => 'North Dakota',
            'OH' => 'Ohio',
            'OK' => 'Oklahoma',
            'OR' => 'Oregon',
            'PA' => 'Pennsylvania',
            'RI' => 'Rhode Island',
            'SC' => 'South Carolina',
            'SD' => 'South Dakota',
            'TN' => 'Tennessee',
            'TX' => 'Texas',
            'UT' => 'Utah',
            'VT' => 'Vermont',
            'VA' => 'Virginia',
            'WA' => 'Washington',
            'WV' => 'West Virginia',
            'WI' => 'Wisconsin',
            'WY' => 'Wyoming',
        ];
    }

    public function getImageLocation()
    {
        if ($this->getLogo() !== null) {
            $imageLocation = '/uploads/team/'.$this->getLogo();
        } else {
            $imageLocation = '/uploads/default-placeholder.jpg';
        }

        return $imageLocation;
    }

    public function currentRanking()
    {
        if (count($this->rankings)) {
            return $this->rankings->last();
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
     * @ORM\Column(name="name_abbr", type="string", length=255)
     */
    private $nameAbbr;

    /**
     * @ORM\Column(name="slug", type="string", length=255, unique=true)
     */
    protected $slug;

    /**
     * @ORM\Column(name="mascot", type="string", length=255)
     */
    private $mascot;

    /**
     * @ORM\Column(name="primary_color", type="string", length=255)
     */
    private $primaryColor;

    /**
     * @ORM\Column(name="secondary_color", type="string", length=255)
     */
    private $secondaryColor;

    /**
     * @ORM\Column(name="city", type="string", length=255)
     */
    private $city;

    /**
     * @ORM\Column(name="state", type="string", length=255)
     */
    private $state;

    /**
     * @ORM\Column(name="school", type="string", length=255)
     */
    private $school;

    /**
     * @ORM\Column(name="stadium_name", type="string", length=255)
     */
    private $stadiumName;

    /**
     * @ORM\Column(name="sub_conference", type="string", length=255, nullable=true)
     */
    private $subConference;

    /**
     * @ORM\Column(name="logo", type="string", nullable=true)
     */
    private $logo;

    /**
     * @ORM\ManyToOne(targetEntity="Conference", inversedBy="teams")
     * @ORM\JoinColumn(name="conference", referencedColumnName="id")
     */
    private $conference;

    /**
     * @ORM\OneToMany(targetEntity="Game", mappedBy="homeTeam")
     */
    private $homeGames;

    /**
     * @ORM\OneToMany(targetEntity="Game", mappedBy="awayTeam")
     */
    private $awayGames;

    /**
     * @ORM\OneToMany(targetEntity="Game", mappedBy="winningTeam")
     */
    private $wonGames;

    /**
     * @ORM\OneToMany(targetEntity="Ranking", mappedBy="team")
     */
    private $rankings;


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
     * @return Team
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
     * @return Team
     */
    public function setNameShort($nameShort)
    {
        $this->nameShort = $nameShort;

        $this->slug = strtolower($nameShort);
        $this->slug = str_replace(" ", "-", $this->slug);
        $this->slug = str_replace("&", "-and-", $this->slug);

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
     * Set nameAbbr
     *
     * @param string $nameAbbr
     * @return Team
     */
    public function setNameAbbr($nameAbbr)
    {
        $this->nameAbbr = $nameAbbr;

        return $this;
    }

    /**
     * Get nameAbbr
     *
     * @return string
     */
    public function getNameAbbr()
    {
        return $this->nameAbbr;
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
     * Set mascot
     *
     * @param string $mascot
     * @return Team
     */
    public function setMascot($mascot)
    {
        $this->mascot = $mascot;

        return $this;
    }

    /**
     * Get mascot
     *
     * @return string
     */
    public function getMascot()
    {
        return $this->mascot;
    }

    /**
     * Set primaryColor
     *
     * @param string $primaryColor
     * @return Team
     */
    public function setPrimaryColor($primaryColor)
    {
        $this->primaryColor = $primaryColor;

        return $this;
    }

    /**
     * Get primaryColor
     *
     * @return string
     */
    public function getPrimaryColor()
    {
        return $this->primaryColor;
    }

    /**
     * Set secondaryColor
     *
     * @param string $secondaryColor
     * @return Team
     */
    public function setSecondaryColor($secondaryColor)
    {
        $this->secondaryColor = $secondaryColor;

        return $this;
    }

    /**
     * Get secondaryColor
     *
     * @return string
     */
    public function getSecondaryColor()
    {
        return $this->secondaryColor;
    }

    /**
     * Set city
     *
     * @param string $city
     * @return Team
     */
    public function setCity($city)
    {
        $this->city = $city;

        return $this;
    }

    /**
     * Get city
     *
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * Set state
     *
     * @param string $state
     * @return Team
     */
    public function setState($state)
    {
        $this->state = $state;

        return $this;
    }

    /**
     * Get state
     *
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Set school
     *
     * @param string $school
     * @return Team
     */
    public function setSchool($school)
    {
        $this->school = $school;

        return $this;
    }

    /**
     * Get school
     *
     * @return string
     */
    public function getSchool()
    {
        return $this->school;
    }

    /**
     * Set stadiumName
     *
     * @param string $stadiumName
     * @return Team
     */
    public function setStadiumName($stadiumName)
    {
        $this->stadiumName = $stadiumName;

        return $this;
    }

    /**
     * Get stadiumName
     *
     * @return string
     */
    public function getStadiumName()
    {
        return $this->stadiumName;
    }

    /**
     * Set subConference
     *
     * @param string $subConference
     * @return Team
     */
    public function setSubConference($subConference)
    {
        $this->subConference = $subConference;

        return $this;
    }

    /**
     * Get subConference
     *
     * @return string
     */
    public function getSubConference()
    {
        return $this->subConference;
    }

    /**
     * Set logo
     *
     * @param string $logo
     * @return Team
     */
    public function setLogo($logo)
    {
        $this->logo = $logo;

        return $this;
    }

    /**
     * Get logo
     *
     * @return string
     */
    public function getLogo()
    {
        return $this->logo;
    }

    /**
     * Set conference
     *
     * @param Conference $conference
     * @return Team
     */
    public function setConference(Conference $conference)
    {
        $this->conference = $conference;

        return $this;
    }

    /**
     * Get conference
     *
     * @return Conference
     */
    public function getConference()
    {
        return $this->conference;
    }

    /**
     * Get homeGames
     *
     * @return Game
     */
    public function getHomeGames()
    {
        return $this->homeGames;
    }

    /**
     * Get awayGames
     *
     * @return Game
     */
    public function getAwayGames()
    {
        return $this->awayGames;
    }

    /**
     * Get wonGames
     *
     * @return Game
     */
    public function getWonGames()
    {
        return $this->wonGames;
    }

    /**
     * Get rankings
     *
     * @return Ranking
     */
    public function getRankings()
    {
        return $this->rankings;
    }
}
