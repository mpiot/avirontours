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

namespace App\Security\Voter;

use App\Entity\User;
use App\Repository\LicenseRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ValidLicenseVoter extends Voter
{
    public const string VALID_LICENSE = 'VALID_LICENSE';

    /** @var array<string, bool> */
    private array $cache = [];

    public function __construct(private readonly LicenseRepository $licenseRepository)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::VALID_LICENSE === $attribute;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        return $this->cache[$user->getUserIdentifier()] ??= $this->licenseRepository->hasValidLicenseForActiveSeason($user);
    }
}
