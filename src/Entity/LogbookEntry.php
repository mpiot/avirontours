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

use App\Validator as AppAssert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @ORM\Entity(repositoryClass="App\Repository\LogbookEntryRepository")
 * @ORM\HasLifecycleCallbacks
 */
class LogbookEntry
{
    public const NUM_ITEMS = 20;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Shell", inversedBy="logbookEntries")
     * @ORM\JoinColumn(nullable=false)
     * @Assert\NotNull(groups={"start", "edit"})
     * @AppAssert\ShellAvailable(groups={"start"})
     * @AppAssert\ShellNotDamaged(groups={"start"})
     */
    private $shell;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\User", inversedBy="logbookEntries")
     * @Assert\NotNull(groups={"start", "edit"})
     * @AppAssert\CrewAvailable(groups={"start"})
     */
    private $crewMembers;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private $nonUserCrewMembers = [];

    /**
     * @ORM\Column(type="date")
     * @Assert\NotNull(groups={"start", "edit"})
     */
    private $date;

    /**
     * @ORM\Column(type="time")
     * @Assert\NotBlank(groups={"start", "edit"})
     */
    private $startAt;

    /**
     * @ORM\Column(type="time", nullable=true)
     * @Assert\NotBlank(groups={"finish"})
     */
    private $endAt;

    /**
     * @ORM\Column(type="float", nullable=true)
     * @Assert\NotBlank(groups={"finish"})
     * @Assert\LessThanOrEqual(30, groups={"finish"})
     * @Assert\GreaterThanOrEqual(1, groups={"finish"})
     */
    private $coveredDistance;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\ShellDamage", mappedBy="logbookEntry", cascade={"persist", "remove"})
     * @Assert\Valid
     */
    private $shellDamages;

    public function __construct()
    {
        $this->crewMembers = new ArrayCollection();
        $this->date = new \DateTime();
        $this->startAt = new \DateTime();
        $this->shellDamages = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getShell(): ?Shell
    {
        return $this->shell;
    }

    public function setShell(?Shell $shell): self
    {
        $this->shell = $shell;

        return $this;
    }

    /**
     * @return Collection|User[]
     */
    public function getCrewMembers(): Collection
    {
        return $this->crewMembers;
    }

    public function addCrewMember(User $crewMember): self
    {
        if (!$this->crewMembers->contains($crewMember)) {
            $this->crewMembers[] = $crewMember;
        }

        return $this;
    }

    public function removeCrewMember(User $crewMember): self
    {
        if ($this->crewMembers->contains($crewMember)) {
            $this->crewMembers->removeElement($crewMember);
        }

        return $this;
    }

    public function getNonUserCrewMembers(): ?array
    {
        return $this->nonUserCrewMembers;
    }

    public function setNonUserCrewMembers(?array $nonUserCrewMembers): self
    {
        $this->nonUserCrewMembers = $nonUserCrewMembers;

        return $this;
    }

    public function getFullCrew(): array
    {
        $crewMembers = $this->crewMembers->map(fn (User $user) => $user->getFullName())->toArray();

        return array_merge($crewMembers, $this->nonUserCrewMembers);
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getStartAt(): ?\DateTimeInterface
    {
        return $this->startAt;
    }

    public function setStartAt(?\DateTimeInterface $startAt): self
    {
        $this->startAt = $startAt;

        return $this;
    }

    public function getEndAt(): ?\DateTimeInterface
    {
        return $this->endAt;
    }

    public function setEndAt(?\DateTimeInterface $endAt): self
    {
        $this->endAt = $endAt;

        return $this;
    }

    public function getCoveredDistance(): ?float
    {
        return $this->coveredDistance;
    }

    public function setCoveredDistance(?float $coveredDistance): self
    {
        $this->coveredDistance = $coveredDistance;

        return $this;
    }

    /**
     * @return Collection|ShellDamage[]
     */
    public function getShellDamages(): Collection
    {
        return $this->shellDamages;
    }

    public function addShellDamage(ShellDamage $shellDamage): self
    {
        if (!$this->shellDamages->contains($shellDamage)) {
            $this->shellDamages[] = $shellDamage;
            $shellDamage->setLogbookEntry($this);
            $shellDamage->setShell($this->getShell());
        }

        return $this;
    }

    public function removeShellDamage(ShellDamage $shellDamage): self
    {
        if ($this->shellDamages->contains($shellDamage)) {
            $this->shellDamages->removeElement($shellDamage);
            // set the owning side to null (unless already changed)
            if ($shellDamage->getLogbookEntry() === $this) {
                $shellDamage->setLogbookEntry(null);
            }
        }

        return $this;
    }

    /**
     * @Assert\Callback(groups={"start"})
     */
    public function validateCrewLength(ExecutionContextInterface $context, $payload): void
    {
        if (null === $this->getShell()) {
            return;
        }

        $numberCrewMembers = $this->getCrewMembers()->count() + \count($this->nonUserCrewMembers);

        if ($numberCrewMembers !== $this->getShell()->getCrewSize()) {
            $context->buildViolation('Le nombre de membre d\'équipage ne correspond pas au nombre de place.')
                ->atPath('crewMembers')
                ->addViolation();
        }
    }

    /**
     * @Assert\Callback(groups={"start"})
     */
    public function validateCrewRowerCategory(ExecutionContextInterface $context): void
    {
        if (null === $this->getShell()) {
            return;
        }

        if ($this->getCrewMembers()->isEmpty()) {
            return;
        }

        if (null !== $this->getShell()) {
            $invalidCrewMembers = [];
            foreach ($this->getCrewMembers() as $crewMember) {
                if ($crewMember->getRowerCategory() > $this->getShell()->getRowerCategory()) {
                    $invalidCrewMembers[] = $crewMember->getFullName();
                }
            }
        }

        if (!empty($invalidCrewMembers)) {
            $context->buildViolation('Certains membres d\'équipage ne sont pas autorisé sur ce bâteau: {{ invalidMembers }}.')
                ->setParameter('{{ invalidMembers }}', implode(', ', $invalidCrewMembers))
                ->atPath('crewMembers')
                ->addViolation();
        }
    }

    /**
     * @Assert\Callback(groups={"start"})
     */
    public function validateCrewRowerLogbookEntry(ExecutionContextInterface $context): void
    {
        if ($this->getCrewMembers()->isEmpty()) {
            return;
        }

        $invalidCrewMembers = [];
        foreach ($this->getCrewMembers() as $crewMember) {
            if ($crewMember->getLicenses()->isEmpty()) {
                continue;
            }

            $logbookEntryLimit = $crewMember->getLicenses()->last()->getLogbookEntryLimit();
            if (null !== $logbookEntryLimit && $crewMember->getLogbookEntries()->count() >= $logbookEntryLimit) {
                $invalidCrewMembers[] = $crewMember->getFullName();
            }
        }

        if (!empty($invalidCrewMembers)) {
            $context->buildViolation('Certains membres d\'équipage ont atteint leur limite de nombre de sorties: {{ invalidMembers }}.')
                ->setParameter('{{ invalidMembers }}', implode(', ', $invalidCrewMembers))
                ->atPath('crewMembers')
                ->addViolation();
        }
    }
}
