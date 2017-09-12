<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;

use AppBundle\Traits\TimestampableTrait;
use AppBundle\Entity\Team;

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

    /**
     * @Assert\Callback
     */
    public function validate(ExecutionContextInterface $context, $payload)
    {
        if ($this->phoneNumber && ! preg_match('/^[1-9][0-9]{9}$/', $this->phoneNumber)) {
            $context->buildViolation('Invalid phone number (ex: 1234567890)')
                ->atPath('phoneNumber')
                ->addViolation();
        } elseif ($this->phoneNumber && ! $this->phoneCarrier) {
            $context->buildViolation('Carrier required for phone number')
                ->atPath('phoneCarrier')
                ->addViolation();
        }
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

    public function getPhoneLink()
    {
        if ($this->phoneNumber && $this->textSubscription) {
            return $this->phoneNumber.'@'.$this->getPhoneCarrierAddress();
        }

        return null;
    }

    private function getPhoneCarrierAddress()
    {
        if ($this->phoneCarrier == 'verizon') {
            return 'vtext.com';
        }

        return 'text.att.net';
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
     * @ORM\Column(name="phone_number", type="string", length=10, nullable=true)
     */
    private $phoneNumber;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Team")
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

    /**
     * @ORM\Column(name="temp_password", type="string", length=255, nullable=true)
     */
    private $tempPassword;

    /**
     * @ORM\Column(name="email_subscription", type="boolean")
     */
    private $emailSubscription = true;

    /**
     * @ORM\Column(name="text_subscription", type="boolean")
     */
    private $textSubscription = false;

    /**
     * @ORM\Column(name="phone_carrier", type="string", length=255, nullable=true)
     * @Assert\Choice(choices={"att", "verizon"}, strict=true)
     */
    private $phoneCarrier;



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
     * Set phoneNumber
     *
     * @param int $phoneNumber
     * @return Person
     */
    public function setPhoneNumber($phoneNumber)
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }

    /**
     * Get phoneNumber
     *
     * @return int
     */
    public function getPhoneNumber()
    {
        return $this->phoneNumber;
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

    /**
     * Set tempPassword
     *
     * @param string $tempPassword
     * @return Person
     */
    public function setTempPassword($tempPassword)
    {
        $this->tempPassword = $tempPassword;

        return $this;
    }

    /**
     * Get tempPassword
     *
     * @return string
     */
    public function getTempPassword()
    {
        return $this->tempPassword;
    }

    /**
     * Set emailSubscription
     *
     * @param bool $emailSubscription
     * @return Person
     */
    public function setEmailSubscription($emailSubscription)
    {
        $this->emailSubscription = $emailSubscription;

        return $this;
    }

    /**
     * Get emailSubscription
     *
     * @return bool
     */
    public function hasEmailSubscription()
    {
        return $this->emailSubscription;
    }

    /**
     * Set textSubscription
     *
     * @param bool $textSubscription
     * @return Person
     */
    public function setTextSubscription($textSubscription)
    {
        $this->textSubscription = $textSubscription;

        return $this;
    }

    /**
     * Get textSubscription
     *
     * @return bool
     */
    public function hasTextSubscription()
    {
        return $this->textSubscription;
    }

    /**
     * Set phoneCarrier
     *
     * @param string $phoneCarrier
     * @return Person
     */
    public function setPhoneCarrier($phoneCarrier)
    {
        $this->phoneCarrier = $phoneCarrier;

        return $this;
    }

    /**
     * Get phoneCarrier
     *
     * @return string
     */
    public function getPhoneCarrier()
    {
        return $this->phoneCarrier;
    }
}
