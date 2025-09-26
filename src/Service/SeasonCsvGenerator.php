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

namespace App\Service;

use App\Entity\License;
use App\Entity\MedicalCertificate;
use App\Entity\Season;
use App\Entity\SeasonCategory;
use App\Repository\LicenseRepository;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Serializer;

class SeasonCsvGenerator
{
    public function __construct(private readonly LicenseRepository $licenseRepository)
    {
    }

    public function exportContacts(Season $season): ?string
    {
        $licenses = $this->licenseRepository->findForContactExport($season);

        if ([] === $licenses) {
            return null;
        }

        $data = [];
        foreach ($licenses as $license) {
            $user = $license->getUser();

            $data[] = [
                'Prénom - Nom' => $license->getUser()->getFullName(),
                'Email' => $license->getUser()->getEmail(),
                'Autorise email club' => $license->getUser()->getClubEmailAllowed() ? 'Oui' : 'Non',
            ];

            if (null !== $user->getFirstLegalGuardian()) {
                $data[] = [
                    'Prénom - Nom' => $user->getFirstLegalGuardian()->getFullName(),
                    'Email' => $user->getFirstLegalGuardian()->getEmail(),
                    'Autorise email club' => $license->getUser()->getClubEmailAllowed() ? 'Oui' : 'Non',
                ];
            }

            if (null !== $user->getSecondLegalGuardian()) {
                $data[] = [
                    'Prénom - Nom' => $user->getSecondLegalGuardian()->getFullName(),
                    'Email' => $user->getSecondLegalGuardian()->getEmail(),
                    'Autorise email club' => $license->getUser()->getClubEmailAllowed() ? 'Oui' : 'Non',
                ];
            }
        }

        $serializer = new Serializer([], [new CsvEncoder()]);

        return $serializer->serialize($data, 'csv', [CsvEncoder::DELIMITER_KEY => ';']);
    }

    public function exportPayments(Season $season): ?string
    {
        $licenses = $this->licenseRepository->findForPaymentsExport($season);
        if ([] === $licenses) {
            return null;
        }

        $headers = [];
        foreach ($licenses as $license) {
            $counter = [];
            foreach ($license->getPayments() as $payment) {
                $header = $payment->getMethod()->label();
                if (false === \array_key_exists($header, $counter)) {
                    $counter[$header] = 0;
                }

                $count = ++$counter[$header];
                $header = 1 === $count ? $header : \sprintf('%s %d', $header, $count);

                if (false === \in_array($header, $headers, true)) {
                    $headers[] = $header;
                }
            }
        }

        sort($headers);
        array_unshift($headers, 'Prénom', 'Nom');

        $data = [];
        foreach ($licenses as $license) {
            $tmpData = array_fill_keys($headers, null);
            $tmpData['Prénom'] = $license->getUser()->getFirstName();
            $tmpData['Nom'] = $license->getUser()->getLastName();

            $counter = [];
            foreach ($license->getPayments() as $payment) {
                $paymentMethod = $payment->getMethod()->label();
                if (false === \array_key_exists($paymentMethod, $counter)) {
                    $counter[$paymentMethod] = 0;
                }

                $count = ++$counter[$paymentMethod];
                $paymentMethod = 1 === $count ? $paymentMethod : \sprintf('%s %d', $paymentMethod, $count);

                $tmpData[$paymentMethod] = $payment->getAmount() / 100;
            }

            $data[] = $tmpData;
        }

        $serializer = new Serializer([], [new CsvEncoder()]);

        return $serializer->serialize($data, 'csv', [CsvEncoder::DELIMITER_KEY => ';']);
    }

    public function exportLicenses(Season $season): ?string
    {
        $licenses = $this->licenseRepository->findForLicenseExport($season);

        if ([] === $licenses) {
            return null;
        }

        $data = [];
        foreach ($licenses as $license) {
            $user = $license->getUser();

            $data[] = [
                'Code adhérent' => $user->getLicenseNumber(),
                'Civilité' => $user->getTextCivility(),
                'Nom' => $user->getLastName(),
                'Prénom' => $user->getFirstName(),
                'Date de naissance' => $user->getBirthday()->format('d/m/Y'),
                'Nom de naissance' => '',
                'Pays de naissance' => '',
                'Nationalité' => $user->getNationality(),
                'Département de naissance' => '',
                'Commune de naissance' => '',
                'Lieu de naissance' => '',
                'N° de voie' => '',
                'Type de voie' => '',
                'Nom de voie' => '',
                'Bâtiment' => '',
                'Escalier' => '',
                'Lieu dit' => '',
                'Code postal' => '',
                'Commune' => '',
                'Pays' => '',
                'Mail' => $user->getEmail(),
                'Mail pro' => '',
                'Tél. fixe' => '',
                'Tél. fixe secondaire' => '',
                'Tél. mobile' => '',
                'Tél. mobile secondaire' => '',
                'Nom du représentant légal' => $user->getFirstLegalGuardian()?->getLastName() ?? '',
                'Prénom du représentant légal' => $user->getFirstLegalGuardian()?->getFirstName() ?? '',
                'Tél. du représentant légal' => $user->getFirstLegalGuardian()?->getPhoneNumber() ?? '',
                'Mail du représentant légal' => $user->getFirstLegalGuardian()?->getEmail() ?? '',
                'Nom du représentant légal secondaire' => $user->getSecondLegalGuardian()?->getLastName() ?? '',
                'Prénom du représentant légal secondaire' => $user->getSecondLegalGuardian()?->getFirstName() ?? '',
                'Tél. du représentant légal secondaire' => $user->getSecondLegalGuardian()?->getPhoneNumber() ?? '',
                'Mail du représentant légal secondaire' => $user->getSecondLegalGuardian()?->getEmail() ?? '',
                'Attestation Natation' => '',
                'Avec IA' => 'Non',
                'Type de licence' => $this->getLicenceType($license),
                'Date de début de validité' => (new \DateTime())->format('d/m/Y'),
                'Manifestation' => '',
                'Honorabilite' => '',
                'Questionnaire Santé Négatif' => $this->getAttestationValue($license),
                'Nom du médecin' => '',
                'RPPS du médecin' => '',
                'Date Certificat Médical' => $this->getMedicalCertificateDate($license),
                'Certificat Médical validé' => 'Oui',
                'AVIRON' => 'Oui',
                'I.A. Sport+' => $license->getOptionalInsurance() ? 'Oui' : 'Non',
            ];
        }

        $serializer = new Serializer([], [new CsvEncoder()]);

        // the FFA server want a CSV without enclosure, set a special enclosure, then remove it
        $csv = $serializer->serialize($data, 'csv', [CsvEncoder::DELIMITER_KEY => ';', CsvEncoder::ENCLOSURE_KEY => \chr(127)]);

        return str_replace(\chr(127), '', $csv);
    }

    private function getMedicalCertificateDate(License $license): string
    {
        // If this is a Certificate
        if (MedicalCertificate::TYPE_CERTIFICATE === $license->getMedicalCertificate()->getType()) {
            return $license->getMedicalCertificate()->getDate()->format('d/m/Y');
        }

        // Else, this is an attestation: we must return the date of the latest Certificate we have
        /** @var License|false $latestLicenceWithCertificate */
        $latestLicenceWithCertificate = $license->getUser()->getLicenses()->filter(fn (License $license): bool => MedicalCertificate::TYPE_CERTIFICATE === $license->getMedicalCertificate()->getType())->last();

        // If we do not retrieve a licence with a medical certificate
        // 1. For user over or equal to 18 years old, return a mistake
        // 2. For user less than 18 yo, it's ok, return nothing
        if (false === $latestLicenceWithCertificate) {
            return 18 > $license->getUser()->getAge() ? '' : '??????';
        }

        return $latestLicenceWithCertificate->getMedicalCertificate()->getDate()->format('d/m/Y');
    }

    private function getAttestationValue(License $license): string
    {
        return MedicalCertificate::TYPE_ATTESTATION === $license->getMedicalCertificate()->getType() ? 'Oui' : 'Non';
    }

    private function getLicenceType(License $license): string
    {
        $seasonCategory = $license->getSeasonCategory()->getLicenseType();
        $level = MedicalCertificate::LEVEL_PRACTICE === $license->getMedicalCertificate()->getLevel() ? 'practice' : 'competition';

        return match ([$seasonCategory, $level]) {
            [SeasonCategory::LICENSE_TYPE_ANNUAL, 'practice'] => 'AL',
            [SeasonCategory::LICENSE_TYPE_ANNUAL, 'competition'] => 'AC',
            [SeasonCategory::LICENSE_TYPE_INDOOR, 'practice'] => 'IL',
            [SeasonCategory::LICENSE_TYPE_INDOOR, 'competition'] => 'IC',
            [SeasonCategory::LICENSE_TYPE_UNIVERSITY, 'practice'] => 'UL',
            [SeasonCategory::LICENSE_TYPE_UNIVERSITY, 'competition'] => 'UC',
            [SeasonCategory::LICENSE_TYPE_DISCOVERY_7D, 'practice'], [SeasonCategory::LICENSE_TYPE_DISCOVERY_7D, 'competition'] => 'D7',
            [SeasonCategory::LICENSE_TYPE_DISCOVERY_30D, 'practice'], [SeasonCategory::LICENSE_TYPE_DISCOVERY_30D, 'competition'] => 'D30',
            [SeasonCategory::LICENSE_TYPE_DISCOVERY_90D, 'practice'], [SeasonCategory::LICENSE_TYPE_DISCOVERY_90D, 'competition'] => 'D90',
            default => '',
        };
    }
}
