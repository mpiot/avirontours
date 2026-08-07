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

namespace App\Validator;

use App\Attestation\AttestationRefusal;
use App\Attestation\AttestationValidity;
use App\Entity\License;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Turns an AttestationValidity into violations.
 *
 * The rules live there, not here, so that the registration form can stop offering a choice this
 * validator would refuse — including which refusal takes precedence. What is left here is the mapping to
 * copy, and the one ordering decision that is presentation rather than rule: a refusal is stated before
 * the level an attestation would have to carry, because there is no point correcting a level on a
 * document that is closed anyway.
 */
class AttestationValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof Attestation) {
            throw new UnexpectedTypeException($constraint, Attestation::class);
        }

        if (null === $value) {
            return;
        }

        if (!$value instanceof License) {
            throw new \Exception('The AttestationValidator must be used on a License.');
        }

        if (true !== $value->getMedicalCertificate()?->isAttestation()) {
            return;
        }

        $validity = AttestationValidity::forLicense($value);
        if (null !== $validity->refusal) {
            [$message, $parameters] = match ($validity->refusal) {
                AttestationRefusal::FirstLicense => [$constraint->firstLicenseMessage, []],
                AttestationRefusal::MajorityReached => [$constraint->majorityReachedMessage, []],
                AttestationRefusal::CompetitionCertificateExpired => [$constraint->competitionMessage, [
                    '{{ date }}' => $validity->supportingCertificate->getDate()->format('d/m/Y'),
                    '{{ expiryDate }}' => $validity->certificateExpiryDate->format('d/m/Y'),
                ]],
                AttestationRefusal::EnrolmentGap => [$constraint->enrolmentGapMessage, [
                    '{{ season }}' => (string) $validity->missingSeasonYear,
                ]],
            };

            $this->context->buildViolation($message)
                ->setParameters($parameters)
                ->atPath('medicalCertificate.type')
                ->addViolation()
            ;

            return;
        }

        $requiredLevel = $validity->getRequiredLevel();
        if (null !== $requiredLevel && $requiredLevel !== $value->getMedicalCertificate()->getLevel()) {
            $this->context->buildViolation($constraint->levelMessage)
                ->setParameter('{{ level }}', $validity->getRequiredLevel()->label())
                ->atPath('medicalCertificate.level')
                ->addViolation()
            ;
        }
    }
}
