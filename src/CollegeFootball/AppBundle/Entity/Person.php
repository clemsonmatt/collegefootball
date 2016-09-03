<?php

namespace CollegeFootball\AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;

use CollegeFootball\AppBundle\Traits\TimestampableTrait;
use CollegeFootball\TeamBundle\Entity\Team;

/**
 * @ORM\Entity()
 * @UniqueEntity(fields={"username"}, message="This username is already taken.")
 * @ORM\Table(name="person")
 */
class Person implements AdvancedUserInterface
{
    use TimestampableTrait;

    public function __toString()
    {
        return $this->firstName.' '.$this->lastName;
    }

    public function getPredictionWins()
    {
        $wins = 0;

        foreach ($this->predictions as $prediction) {
            if ($prediction->getGame()->getWinningTeam() == $prediction->getTeam()) {
                $wins++;
            }
        }

        return $wins;
    }

    public function getPredictionLosses()
    {
        $losses = 0;

        foreach ($this->predictions as $prediction) {
            if ($prediction->getGame()->getWinningTeam() && $prediction->getGame()->getWinningTeam() != $prediction->getTeam()) {
                $losses++;
            }
        }

        return $losses;
    }

    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(name="username", type="string", length=255)
     * @Assert\Length(
     *     min = 4,
     *     minMessage = "Username should be at least 4 chars long",
     *     max = 50,
     *     maxMessage = "Username should be no more than 50 chars long"
     * )
     * @Assert\NotBlank()
     */
    private $username;

    /**
     * @ORM\Column(name="password", type="string", length=255, nullable=true)
     * @Assert\Length(
     *     min = 8,
     *     minMessage = "Password should be at least 8 chars long",
     *     max = 255,
     *     maxMessage = "Password should be no more than 255 chars long"
     * )
     */
    private $password;

    /**
     * @ORM\Column(name="first_name", type="string", length=255)
     * @Assert\NotBlank()
     */
    private $firstName;

    /**
     * @ORM\Column(name="last_name", type="string", length=255)
     * @Assert\NotBlank()
     */
    private $lastName;

    /**
     * @ORM\Column(name="email", type="string", length=255, nullable=true)
     */
    private $email;

    /**
     * @ORM\ManyToOne(targetEntity="CollegeFootball\TeamBundle\Entity\Team")
     * @ORM\JoinColumn(name="team_id", referencedColumnName="id", nullable=true)
     */
    private $team;

    /**
     * @ORM\OneToMany(targetEntity="Prediction", mappedBy="person")
     */
    private $predictions;

    /**
     * @ORM\Column(name="roles", type="array")
     */
    private $roles = [];



    public function isAccountNonExpired()
    {
        return true;
    }

    public function isAccountNonLocked()
    {
        return true;
    }

    public function isCredentialsNonExpired()
    {
        return true;
    }

    public function isEnabled()
    {
        return true;
    }

    public function getSalt()
    {
        return null;
    }

    public function eraseCredentials()
    {
    }


    /**
     * Get id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set username
     *
     * @param string $username
     * @return Person
     */
    public function setUsername($username)
    {
        $username = strtolower($username);

        $this->username = $username;

        return $this;
    }

    /**
     * Get username
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Set password
     *
     * @param string $password
     * @return Person
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get password
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Get firstName
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * Set firstName
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * Get lastName
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * Set lastName
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * Set email
     *
     * @param string $email
     * @return Person
     */
    public function setEmail($email = null)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set team
     *
     * @param Team $team
     * @return Person
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

    /**
     * Set predictions
     *
     * @param Prediction $predictions
     * @return Person
     */
    public function setPredictions($predictions)
    {
        $this->predictions = $predictions;

        return $this;
    }

    /**
     * Get predictions
     *
     * @return Prediction
     */
    public function getPredictions()
    {
        return $this->predictions;
    }

    /**
     * Set roles
     *
     * @param array $roles
     * @return Person
     */
    public function setRoles($roles)
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * Get roles
     *
     * @return array
     */
    public function getRoles()
    {
        $roles = [];

        $roles[] = 'ROLE_USER';

        foreach ($this->roles as $role) {
            $roles[] = $role;
        }

        return $roles;
    }
}
