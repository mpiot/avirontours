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

namespace App\Controller;

use Doctrine\Persistence\ManagerRegistry;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\OAuth2Client;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Symfony\Component\Routing\Attribute\Route;

class Concept2OauthController extends AbstractController
{
    #[Route('/oauth/concept-logbook/connect', name: 'oauth_concept2_connect')]
    public function connectAction(ClientRegistry $clientRegistry)
    {
        return $clientRegistry
            ->getClient('concept2')
            ->redirect([], [])
        ;
    }

    #[Route('/oauth/concept-logbook', name: 'oauth_concept2_check', host: 'my.avirontours.fr')]
    #[Route('/oauth/concept-logbook')]
    public function connectCheckAction(ClientRegistry $clientRegistry, ManagerRegistry $managerRegistry)
    {
        /** @var OAuth2Client $client */
        $client = $clientRegistry->getClient('concept2');

        try {
            $accessToken = $client->getAccessToken();
            $this->getUser()->setConcept2RefreshToken($accessToken->getRefreshToken());
            $managerRegistry->getManager()->flush();

            $this->addFlash('success', 'Votre compte Concept2 a bien été connecté.');
        } catch (IdentityProviderException $e) {
            $this->addFlash('error', 'Une erreur est survenue lors de la connexion de votre compte Concept2.');
        }

        return $this->redirectToRoute('sport_profile_configuration');
    }

    #[Route('/oauth/concept-logbook/unconnect', name: 'oauth_concept2_unconnect')]
    public function unconnectAction(ClientRegistry $clientRegistry, ManagerRegistry $managerRegistry)
    {
        $this->getUser()->setConcept2RefreshToken(null);
        $managerRegistry->getManager()->flush();

        $this->addFlash('success', 'Votre compte Concept2 a bien été déconnecté.');

        return $this->redirectToRoute('sport_profile_configuration');
    }
}
