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

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_CLASS)]
class Attestation extends Constraint
{
    public string $firstLicenseMessage = 'Une attestation QS-Sport n\'est acceptée qu\'en renouvellement: un certificat médical est exigé pour une première licence.';

    public string $majorityReachedMessage = 'Vous atteignez la majorité: un premier certificat médical en tant que majeur est exigé, une attestation QS-Sport ne suffit plus.';

    public string $levelMessage = 'Votre certificat médical est de niveau {{ level }}.';

    public string $competitionMessage = 'Une licence compétition exige un certificat médical valable 3 ans, encore valide lors de la transmission de votre licence à la fédération. Le vôtre, daté du {{ date }}, expire le {{ expiryDate }}. Fournissez un certificat médical plus récent.';

    public string $enrolmentGapMessage = 'Votre inscription au club a été interrompue (saison {{ season }} manquante) depuis votre dernier certificat médical: un nouveau certificat médical est exigé.';

    #[\Override]
    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
