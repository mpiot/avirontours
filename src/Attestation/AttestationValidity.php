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

namespace App\Attestation;

use App\Entity\License;
use App\Entity\MedicalCertificate;
use App\Entity\User;
use App\Enum\CertificateLevel;
use App\Enum\CertificateType;

/**
 * Is an attestation valid for a season ?
 *
 * Based on https://www.ffaviron.fr/prendre-une-licence/certificat-medical/
 */
final readonly class AttestationValidity
{
    private const string CERTIFICATE_VALIDITY = '+3 years';
    private const string FEDERAL_SYNC_MARGIN = '+1 month';

    private function __construct(
        public ?AttestationRefusal $refusal = null,
        public ?MedicalCertificate $supportingCertificate = null,
        public ?\DateTime $certificateExpiryDate = null,
        public ?int $missingSeasonYear = null,
    ) {
    }

    public function isAvailable(): bool
    {
        return null === $this->refusal;
    }

    /** The level an attestation is locked to, or null when the member may choose freely. */
    public function getRequiredLevel(): ?CertificateLevel
    {
        return $this->supportingCertificate?->getLevel();
    }

    public static function forLicense(License $license): self
    {
        $user = $license->getUser();
        $requestedSeason = $license->getSeasonCategory()?->getSeason()?->getName();
        if (null === $user || null === $user->getBirthday() || null === $requestedSeason) {
            return new self();
        }

        // A minor can always use an attestation, at any level.
        if (false === $user->isMajor()) {
            return new self();
        }

        $previousLicenses = self::getUserPreviousLicenses($user, $license);
        $latestLicenseWithCertificate = self::getLatestLicenseWithCertificate($previousLicenses, $user);
        if (null === $latestLicenseWithCertificate) {
            return new self(
                refusal: [] === $previousLicenses
                    ? AttestationRefusal::FirstLicense
                    : AttestationRefusal::MajorityReached,
            );
        }

        $certificate = $latestLicenseWithCertificate->getMedicalCertificate();
        $expiryDate = (clone $certificate->getDate())->modify(self::CERTIFICATE_VALIDITY);
        $missingSeasonYear = self::getMissingSeasonYear(
            $previousLicenses,
            $latestLicenseWithCertificate->getSeasonCategory()->getSeason()->getName(),
            $requestedSeason,
        );

        return new self(
            refusal: self::refuse($certificate, $expiryDate, $missingSeasonYear),
            supportingCertificate: $certificate,
            certificateExpiryDate: $expiryDate,
            missingSeasonYear: $missingSeasonYear,
        );
    }

    private static function refuse(MedicalCertificate $certificate, \DateTime $expiryDate, ?int $missingSeasonYear): ?AttestationRefusal
    {
        if (CertificateLevel::Competition === $certificate->getLevel()
            && $expiryDate < new \DateTime(self::FEDERAL_SYNC_MARGIN)
        ) {
            return AttestationRefusal::CompetitionCertificateExpired;
        }

        return null === $missingSeasonYear ? null : AttestationRefusal::EnrolmentGap;
    }

    /**
     * @return License[]
     */
    private static function getUserPreviousLicenses(User $user, License $currentLicense): array
    {
        return $user->getLicenses()
            ->filter(static fn (License $license) => $currentLicense !== $license)
            ->toArray()
        ;
    }

    /**
     * @param License[] $licenses
     */
    private static function getLatestLicenseWithCertificate(array $licenses, User $user): ?License
    {
        $majorityDate = (clone $user->getBirthday())->modify('+18 years');
        $latest = null;

        foreach ($licenses as $license) {
            $certificate = $license->getMedicalCertificate();
            if (CertificateType::Certificate !== $certificate->getType()) {
                continue;
            }

            // If certificate is dated before majority date, skip it.
            $certificateDate = $certificate->getDate();
            if (null === $certificateDate || $certificateDate < $majorityDate) {
                continue;
            }

            // Define the lastest certificate, use date to order them
            if (null === $latest || $certificateDate > $latest->getMedicalCertificate()->getDate()) {
                $latest = $license;
            }
        }

        return $latest;
    }

    /**
     * @param License[] $licenses
     */
    private static function getMissingSeasonYear(array $licenses, int $certificateSeasonYear, int $requestedSeasonYear): ?int
    {
        if ($requestedSeasonYear <= $certificateSeasonYear) {
            return null;
        }

        $enrolledYears = array_map(
            static fn (License $license) => $license->getSeasonCategory()->getSeason()->getName(),
            $licenses
        );

        $expectedRange = range($certificateSeasonYear, $requestedSeasonYear - 1);
        $missingYears = array_diff($expectedRange, $enrolledYears);

        return [] === $missingYears ? null : max($missingYears);
    }
}
