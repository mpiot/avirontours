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
use App\Entity\Season;
use App\Enum\MedicalCertificateLevel;
use App\Enum\MedicalCertificateType;
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

        if (empty($licenses)) {
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
        if (empty($licenses)) {
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
                $header = 1 === $count ? $header : "{$header} {$count}";

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
                $paymentMethod = 1 === $count ? $paymentMethod : "{$paymentMethod} {$count}";

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

        if (empty($licenses)) {
            return null;
        }

        $data = [];
        foreach ($licenses as $license) {
            $user = $license->getUser();

            $data[] = [
                'CodeAdherent' => $user->getLicenseNumber(),
                'Civilité' => $user->getTextCivility(),
                'NomUsage' => $user->getLastName(),
                'Prenom' => $user->getFirstName(),
                'Prenom2' => '',
                'Prenom3' => '',
                'NomNaissance' => '',
                'Nationalite' => $user->getNationality(),
                'DateNaissance' => $user->getBirthday()->format('d/m/Y'),
                'PaysNaissance' => '',
                'DeptNaissance' => '',
                'VilleNaissance' => '',
                'NomPere' => '',
                'PrenomPere' => '',
                'NomMere' => '',
                'PrenomMere' => '',
                'NumeroVoie' => $user->getLaneNumber(),
                'TypeVoie' => $user->getLaneType(),
                'LibelleVoie' => $user->getLaneName(),
                'ImmBatRes' => '',
                'AptEtageEsc' => '',
                'Lieudit' => '',
                'Cp' => $user->getPostalCode(),
                'Ville' => $user->getCity(),
                'Pays' => 'FR',
                'Telephone' => '',
                'Autre telephone' => '',
                'Mobile' => '',
                'AutreMobile' => '',
                'Email' => $user->getEmail(),
                'AutreEmail' => '',
                'Fax' => '',
                'UtilisationAdresse' => $license->getFederationEmailAllowed() ? 'Oui' : 'Non',
                'SituationFamille' => '',
                'Profession' => '',
                'CategSocioPro' => '',
                'DateSouscription' => (new \DateTime())->format('d/m/Y'),
                'TypeLicence' => $license->getSeasonCategory()->getLicenseType()->value,
                'code manifestation' => '',
                'AssuranceIASportPlus' => $license->getOptionalInsurance() ? 'Oui' : 'Non',
                'Date certificat "Pratique"' => $this->getMedicalCertificateDate($license, MedicalCertificateLevel::Practice),
                'Medecin certificat "Pratique"' => '',
                'N° Medecin du certificat "Pratique"' => '',
                'Attestation santé "pratique"' => $this->getAttestationValue($license, MedicalCertificateLevel::Practice),
                'Date certificat "Compétition"' => $this->getMedicalCertificateDate($license, MedicalCertificateLevel::Competition),
                'Medecin certificat "Compétition"' => '',
                'N° Medecin du certificat "Compétition"' => '',
                'Attestation santé "compétition"' => $this->getAttestationValue($license, MedicalCertificateLevel::Competition),
                'Date certificat "Surclassement"' => $this->getMedicalCertificateDate($license, MedicalCertificateLevel::Upgrade),
                'Medecin certificat "Surclassement"' => '',
                'N° Medecin du certificat "Surclassement"' => '',
                'Entreprise' => 'Non',
                'Nom Entreprise' => '',
                'Pratiquant' => 'Oui',
            ];
        }

        $serializer = new Serializer([], [new CsvEncoder()]);

        // the FFA server want a CSV without enclosure, set a special enclosure, then remove it
        $csv = $serializer->serialize($data, 'csv', [CsvEncoder::DELIMITER_KEY => ';', CsvEncoder::ENCLOSURE_KEY => \chr(127)]);

        return str_replace(\chr(127), '', $csv);
    }

    private function getMedicalCertificateDate(License $license, MedicalCertificateLevel $level): string
    {
        // Check the level
        if ($level !== $license->getMedicalCertificate()->getLevel()) {
            return '';
        }

        // If this is a Certificate
        if (MedicalCertificateType::Certificate === $license->getMedicalCertificate()->getType()) {
            return $license->getMedicalCertificate()->getDate()->format('d/m/Y');
        }

        // Else, this is an attestation: we must return the date of the latest Certificate we have
        /** @var License|bool $latestLicenceWithCertificate */
        $latestLicenceWithCertificate = $license->getUser()->getLicenses()->filter(function (License $license) {
            return MedicalCertificateType::Certificate === $license->getMedicalCertificate()->getType();
        })->last();

        // If we do not retrieve a licence with a medical certificate
        // 1. For user over or equal to 18 years old, return a mistake
        // 2. For user less than 18 yo, it's ok, return nothing
        if (false === $latestLicenceWithCertificate) {
            return 18 > $license->getUser()->getAge() ? '' : '??????';
        }

        return $latestLicenceWithCertificate->getMedicalCertificate()->getDate()->format('d/m/Y');
    }

    private function getAttestationValue(License $license, MedicalCertificateLevel $level): string
    {
        // Check the level
        if ($level !== $license->getMedicalCertificate()->getLevel()) {
            return '';
        }

        return MedicalCertificateType::Attestation === $license->getMedicalCertificate()->getType() ? 'Oui' : 'Non';
    }
}
