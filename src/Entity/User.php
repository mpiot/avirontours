<?php

declare(strict_types=1);

/*
 * Copyright 2020 Mathieu Piot
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *    http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Scheb\TwoFactorBundle\Model\Email\TwoFactorInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;
use function Symfony\Component\String\u;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[UniqueEntity(fields: ['firstName', 'lastName'], message: 'Un compte existe déjà avec ce nom et prénom.', repositoryMethod: 'findForUniqueness')]
#[ORM\Entity(repositoryClass: 'App\Repository\UserRepository')]
#[ORM\Table(name: 'app_user')]
#[ORM\HasLifecycleCallbacks]
class User implements UserInterface, PasswordAuthenticatedUserInterface, TwoFactorInterface, \Stringable
{
    public const NUM_ITEMS = 20;
    public const GENDER_FEMALE = 'f';
    public const GENDER_MALE = 'm';

    #[ORM\Id, ORM\Column(type: Types::INTEGER), ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 180, unique: true)]
    private ?string $username = null;

    #[Assert\NotBlank]
    #[Assert\Email]
    #[ORM\Column(type: Types::STRING, length: 180)]
    private ?string $email = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $phoneNumber = null;

    #[ORM\Column(type: Types::JSON)]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $password = null;

    #[Assert\NotBlank]
    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $gender = null;

    #[Assert\NotBlank]
    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $firstName = null;

    #[Assert\NotBlank]
    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $lastName = null;

    #[Assert\NotBlank]
    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $birthday = null;

    #[Assert\NotBlank]
    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $subscriptionDate;

    #[Assert\NotBlank]
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $laneNumber = null;

    #[Assert\NotNull]
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $laneType = null;

    #[Assert\NotBlank]
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $laneName = null;

    #[Assert\NotBlank]
    #[ORM\Column(type: Types::STRING, length: 5, nullable: true)]
    private ?string $postalCode = null;

    #[Assert\NotBlank]
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $city = null;

    #[ORM\ManyToMany(targetEntity: 'App\Entity\LogbookEntry', mappedBy: 'crewMembers')]
    private Collection $logbookEntries;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: 'App\Entity\License', cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(value: ['id' => 'ASC'])]
    private Collection $licenses;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $authCode = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $clubEmailAllowed;

    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $partnersEmailAllowed;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $licenseNumber = null;

    #[ORM\OneToOne(targetEntity: Physiology::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private ?Physiology $physiology = null;

    #[ORM\OneToOne(targetEntity: Anatomy::class, cascade: ['persist', 'remove'])]
    private ?Anatomy $anatomy = null;

    #[ORM\OneToOne(targetEntity: PhysicalQualities::class, cascade: ['persist', 'remove'])]
    private ?PhysicalQualities $physicalQualities = null;

    #[ORM\OneToOne(targetEntity: WorkoutMaximumLoad::class, cascade: ['persist', 'remove'])]
    private ?WorkoutMaximumLoad $workoutMaximumLoad = null;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Training::class)]
    private Collection $trainings;

    #[ORM\ManyToMany(targetEntity: Group::class, mappedBy: 'members')]
    private Collection $groups;

    public function __construct()
    {
        $this->subscriptionDate = new \DateTime();
        $this->logbookEntries = new ArrayCollection();
        $this->licenses = new ArrayCollection();
        $this->clubEmailAllowed = true;
        $this->partnersEmailAllowed = false;
        $this->trainings = new ArrayCollection();
        $this->groups = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->getFullName();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function getUserIdentifier(): ?string
    {
        return $this->getUsername();
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        if (null !== $email) {
            $email = u($email)->lower()->toString();
        }

        $this->email = $email;

        return $this;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(?string $phoneNumber): self
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = array_values($roles);

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getSalt(): void
    {
        // not needed when using the "bcrypt" algorithm in security.yaml
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function getTextGender(): ?string
    {
        return array_flip(self::getAvailableGenders())[$this->gender];
    }

    public function getTextCivility(): ?string
    {
        return array_flip(self::getAvailableCivilities())[$this->gender];
    }

    public function setGender(string $gender): self
    {
        $this->gender = $gender;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): self
    {
        if (null !== $firstName) {
            $firstName = u($firstName)->lower()->title(true)->toString();
        }

        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): self
    {
        if (null !== $lastName) {
            $lastName = u($lastName)->lower()->title(true)->toString();
        }

        $this->lastName = $lastName;

        return $this;
    }

    public function getFullName(): string
    {
        return $this->getFirstName().' '.$this->getLastName();
    }

    public function getBirthday(): ?\DateTimeInterface
    {
        return $this->birthday;
    }

    public function setBirthday(?\DateTimeInterface $birthday): self
    {
        $this->birthday = $birthday;

        return $this;
    }

    public function getAge(): int
    {
        $birthday = $this->birthday;

        if (null === $birthday) {
            return 0;
        }

        $interval = $birthday->diff(new \DateTime());

        return $interval->y;
    }

    public function getSubscriptionDate(): ?\DateTimeInterface
    {
        return $this->subscriptionDate;
    }

    public function setSubscriptionDate(?\DateTimeInterface $subscriptionDate): self
    {
        $this->subscriptionDate = $subscriptionDate;

        return $this;
    }

    public function getLaneNumber(): ?string
    {
        return $this->laneNumber;
    }

    public function setLaneNumber(?string $laneNumber): self
    {
        $this->laneNumber = $laneNumber;

        return $this;
    }

    public function getLaneType(): ?string
    {
        return $this->laneType;
    }

    public function setLaneType(?string $laneType): self
    {
        $this->laneType = $laneType;

        return $this;
    }

    public function getLaneName(): ?string
    {
        return $this->laneName;
    }

    public function setLaneName(?string $laneName): self
    {
        if (null !== $laneName) {
            $laneName = u($laneName)->lower()->title(true)->toString();
        }

        $this->laneName = $laneName;

        return $this;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function setPostalCode(?string $postalCode): self
    {
        $this->postalCode = $postalCode;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): self
    {
        if (null !== $city) {
            $city = u($city)->lower()->title(true)->toString();
        }

        $this->city = $city;

        return $this;
    }

    public function getFormattedAddress(): string
    {
        $address = "{$this->getLaneNumber()}, {$this->getLaneType()} {$this->getLaneName()}\n";
        $address .= "{$this->getPostalCode()} {$this->getCity()}";

        return $address;
    }

    /**
     * @return Collection|LogbookEntry[]
     */
    public function getLogbookEntries(): Collection
    {
        return $this->logbookEntries;
    }

    /**
     * @return Collection|License[]
     */
    public function getLicenses(): Collection
    {
        return $this->licenses;
    }

    public function addLicense(License $license): self
    {
        if (!$this->licenses->contains($license)) {
            $this->licenses[] = $license;
            $license->setUser($this);
        }

        return $this;
    }

    public function removeLicense(License $license): self
    {
        if ($this->licenses->contains($license)) {
            $this->licenses->removeElement($license);
            // set the owning side to null (unless already changed)
            if ($license->getUser() === $this) {
                $license->setUser(null);
            }
        }

        return $this;
    }

    public function hasValidLicense(): bool
    {
        foreach ($this->licenses as $license) {
            if ($license->isValid() && $license->getSeasonCategory()->getSeason()->getActive()) {
                return true;
            }
        }

        return false;
    }

    public function isEmailAuthEnabled(): bool
    {
        foreach ($this->roles as $role) {
            if (null !== u($role)->indexOf('ADMIN')) {
                return true;
            }
        }

        return false;
    }

    public function getEmailAuthRecipient(): string
    {
        return $this->getEmail();
    }

    public function getEmailAuthCode(): string
    {
        return $this->authCode;
    }

    public function setEmailAuthCode(string $authCode): void
    {
        $this->authCode = $authCode;
    }

    public function getClubEmailAllowed(): ?bool
    {
        return $this->clubEmailAllowed;
    }

    public function setClubEmailAllowed(bool $clubEmailAllowed): self
    {
        $this->clubEmailAllowed = $clubEmailAllowed;

        return $this;
    }

    public function getPartnersEmailAllowed(): ?bool
    {
        return $this->partnersEmailAllowed;
    }

    public function setPartnersEmailAllowed(bool $partnersEmailAllowed): self
    {
        $this->partnersEmailAllowed = $partnersEmailAllowed;

        return $this;
    }

    public function getLicenseNumber(): ?string
    {
        return $this->licenseNumber;
    }

    public function setLicenseNumber(string $licenseNumber): self
    {
        $this->licenseNumber = $licenseNumber;

        return $this;
    }

    public function getPhysiology(): ?Physiology
    {
        return $this->physiology;
    }

    public function setPhysiology(?Physiology $physiology): self
    {
        $this->physiology = $physiology;

        return $this;
    }

    public function getAnatomy(): ?Anatomy
    {
        return $this->anatomy;
    }

    public function setAnatomy(?Anatomy $anatomy): self
    {
        $this->anatomy = $anatomy;

        return $this;
    }

    public function getPhysicalQualities(): ?PhysicalQualities
    {
        return $this->physicalQualities;
    }

    public function setPhysicalQualities(?PhysicalQualities $physicalQualities): self
    {
        $this->physicalQualities = $physicalQualities;

        return $this;
    }

    public function getWorkoutMaximumLoad(): ?WorkoutMaximumLoad
    {
        return $this->workoutMaximumLoad;
    }

    public function setWorkoutMaximumLoad(?WorkoutMaximumLoad $workoutMaximumLoad): self
    {
        $this->workoutMaximumLoad = $workoutMaximumLoad;

        return $this;
    }

    /**
     * @return Collection|Training[]
     */
    public function getTrainings(): Collection
    {
        return $this->trainings;
    }

    public function getTrainingsDuration(string $energyPathway = null): int
    {
        $duration = 0;
        foreach ($this->trainings as $training) {
            if (null !== $energyPathway && $energyPathway !== $training->getEnergyPathway()) {
                continue;
            }

            $duration += $training->getDuration();
        }

        return $duration;
    }

    public function getTrainingsFeeling(): ?float
    {
        if ($this->trainings->isEmpty()) {
            return null;
        }

        $feeling = 0;
        foreach ($this->trainings as $training) {
            $feeling += $training->getFeeling();
        }

        return $feeling / $this->trainings->count();
    }

    /**
     * @return Collection|Group[]
     */
    public function getGroups(): Collection
    {
        return $this->groups;
    }

    public function addGroup(Group $group): self
    {
        if (!$this->groups->contains($group)) {
            $this->groups[] = $group;
            $group->addMember($this);
        }

        return $this;
    }

    public function removeGroup(Group $group): self
    {
        if ($this->groups->removeElement($group)) {
            $group->removeMember($this);
        }

        return $this;
    }

    #[Assert\Callback]
    public function validatePhoneNumber(ExecutionContextInterface $context)
    {
        if (null === $this->birthday) {
            return;
        }

        if ($this->getAge() < 18 && empty($this->phoneNumber)) {
            $context->buildViolation('Le membre est mineur, merci de renseigner un numéro de téléphone.')
                ->atPath('phoneNumber')
                ->addViolation()
            ;
        }
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function defineUsername(): void
    {
        $slugger = new AsciiSlugger('fr');
        $firstName = $slugger->slug($this->firstName)->lower();
        $lastName = $slugger->slug($this->lastName)->lower();
        $this->username = "{$firstName}.{$lastName}";
    }

    public static function getAvailableCivilities(): array
    {
        return [
            'Madame' => self::GENDER_FEMALE,
            'Monsieur' => self::GENDER_MALE,
        ];
    }

    public static function getAvailableGenders(): array
    {
        return [
            'Femme' => self::GENDER_FEMALE,
            'Homme' => self::GENDER_MALE,
        ];
    }
}
