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

namespace App\Security\Voter;

use App\Entity\User;
use App\Repository\SeasonRepository;
use App\Repository\SeasonUserRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class UserRenewVoter extends Voter
{
    private $seasonRepository;
    private $seasonUserRepository;

    public function __construct(SeasonRepository $seasonRepository, SeasonUserRepository $seasonUserRepository)
    {
        $this->seasonRepository = $seasonRepository;
        $this->seasonUserRepository = $seasonUserRepository;
    }

    protected function supports($attribute, $subject)
    {
        return 'RENEW' === $attribute && $subject instanceof User;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        $user = $token->getUser();

        if (!$user instanceof UserInterface) {
            return false;
        }

        $lastUserSeason = $this->seasonUserRepository->findLastUserSeason($user);
        $lastSeason = $this->seasonRepository->findLastSeason();

        if (null === $lastUserSeason || $lastUserSeason->getSeason() !== $lastSeason) {
            return true;
        }

        return false;
    }
}
