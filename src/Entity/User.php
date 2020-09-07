<?php

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
use Doctrine\ORM\Mapping as ORM;
use Scheb\TwoFactorBundle\Model\Email\TwoFactorInterface as EmailTwoFactorInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;
use function Symfony\Component\String\u;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 * @ORM\Table(name="app_user")
 * @UniqueEntity(fields={"firstName", "lastName"}, message="Un compte existe déjà avec ce nom et prénom.", repositoryMethod="findForUniqueness")
 * @ORM\HasLifecycleCallbacks()
 */
class User implements UserInterface, EmailTwoFactorInterface
{
    const NUM_ITEMS = 20;

    const GENDER_FEMALE = 'f';
    const GENDER_MALE = 'm';

    const ROWER_CATEGORY_A = 1;
    const ROWER_CATEGORY_B = 2;
    const ROWER_CATEGORY_C = 3;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=180, unique=true)
     */
    private $username;

    /**
     * @ORM\Column(type="string", length=180)
     * @Assert\NotBlank()
     * @Assert\Email()
     */
    private $email;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private $roles = [];

    /**
     * @var string The hashed password
     * @ORM\Column(type="string", nullable=true)
     */
    private $password;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank()
     */
    private $gender;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank()
     */
    private $firstName;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank()
     */
    private $lastName;

    /**
     * @ORM\Column(type="integer")
     * @Assert\NotBlank()
     */
    private $rowerCategory;

    /**
     * @ORM\Column(type="date")
     * @Assert\NotBlank()
     */
    private $birthday;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $legalRepresentative;

    /**
     * @ORM\Column(type="date")
     * @Assert\NotBlank()
     */
    private $subscriptionDate;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\NotBlank()
     */
    private $laneNumber;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\NotNull()
     */
    private $laneType;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\NotBlank()
     */
    private $laneName;

    /**
     * @ORM\Column(type="string", length=5, nullable=true)
     * @Assert\NotBlank()
     */
    private $postalCode;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\NotBlank()
     */
    private $city;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\NotBlank()
     */
    private $phoneNumber;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\LogbookEntry", mappedBy="crewMembers")
     */
    private $logbookEntries;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\License", mappedBy="user", cascade={"persist", "remove"})
     * @ORM\OrderBy({"id": "ASC"})
     * @Assert\Valid()
     */
    private $licenses;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $authCode;

    /**
     * @ORM\Column(type="boolean")
     */
    private $clubEmailAllowed;

    /**
     * @ORM\Column(type="boolean")
     */
    private $partnersEmailAllowed;

    public function __construct()
    {
        $this->subscriptionDate = new \DateTime();
        $this->rowerCategory = self::ROWER_CATEGORY_C;
        $this->logbookEntries = new ArrayCollection();
        $this->subscriptionDate = new \DateTimeImmutable();
        $this->licenses = new ArrayCollection();
        $this->clubEmailAllowed = true;
        $this->partnersEmailAllowed = false;
    }

    public function __toString()
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

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        if (null !== $email) {
            $email = u($email)->lower();
        }

        $this->email = $email;

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
    public function getPassword(): string
    {
        return (string) $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getSalt()
    {
        // not needed when using the "bcrypt" algorithm in security.yaml
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
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
        $availableGenders = array_flip(self::getAvailableGenders());
        if (!\array_key_exists($this->gender, $availableGenders)) {
            throw new \Exception(sprintf('The gender "%s" is not available, the method "getAvailableGenders" only return that genders: %s.', $this->gender, implode(', ', self::getAvailableGenders())));
        }

        return $availableGenders[$this->gender];
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
            $firstName = u($firstName)->lower()->title(true);
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
            $lastName = u($lastName)->lower()->title(true);
        }

        $this->lastName = $lastName;

        return $this;
    }

    public function getFullName(): string
    {
        return $this->getFirstName().' '.$this->getLastName();
    }

    public function getRowerCategory(): ?int
    {
        return $this->rowerCategory;
    }

    public function getTextRowerCategory(): string
    {
        $availableRowerCategories = array_flip(self::getAvailableRowerCategories());
        if (!\array_key_exists($this->rowerCategory, $availableRowerCategories)) {
            throw new \Exception(sprintf('The rowerCategory "%s" is not available, the method "getAvailableRowerCategories" only return that categories: %s.', $this->rowerCategory, implode(', ', self::getAvailableRowerCategories())));
        }

        return $availableRowerCategories[$this->rowerCategory];
    }

    public function setRowerCategory(int $rowerCategory): self
    {
        $this->rowerCategory = $rowerCategory;

        return $this;
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

    public function getLegalRepresentative(): ?string
    {
        return $this->legalRepresentative;
    }

    public function setLegalRepresentative(?string $legalRepresentative): self
    {
        if (null !== $legalRepresentative) {
            $legalRepresentative = u($legalRepresentative)->lower()->title(true);
        }

        $this->legalRepresentative = $legalRepresentative;

        return $this;
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
            $laneName = u($laneName)->lower()->title(true);
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
            $city = u($city)->lower()->title(true);
        }

        $this->city = $city;

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
        return $this->email;
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

    /**
     * @Assert\Callback()
     */
    public function validateLegalRepresentative(ExecutionContextInterface $context, $payload)
    {
        if ($this->getAge() < 18 && empty($this->legalRepresentative)) {
            $context->buildViolation('Le membre est mineur, merci de renseigner un représentant légal.')
                ->atPath('legalRepresentative')
                ->addViolation();
        }
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function defineUsername()
    {
        $slugger = new AsciiSlugger('fr');
        $firstName = $slugger->slug($this->firstName)->lower();
        $lastName = $slugger->slug($this->lastName)->lower();

        $this->username = "$firstName.$lastName";
    }

    public static function getAvailableGenders(): array
    {
        return [
            'Femme' => self::GENDER_FEMALE,
            'Homme' => self::GENDER_MALE,
        ];
    }

    public static function getAvailableRowerCategories(): array
    {
        return [
            'A' => self::ROWER_CATEGORY_A,
            'B' => self::ROWER_CATEGORY_B,
            'C' => self::ROWER_CATEGORY_C,
        ];
    }
}
