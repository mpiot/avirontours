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

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\OAuth2Client;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class Concept2OauthController extends AbstractController
{
    #[Route('/oauth/concept-logbook/connect', name: 'oauth_concept2_connect')]
    public function connect(ClientRegistry $clientRegistry): Response
    {
        return $clientRegistry
            ->getClient('concept2')
            ->redirect([], [])
        ;
    }

    #[Route('/oauth/concept-logbook', name: 'oauth_concept2_check', host: 'my.avirontours.fr')]
    #[Route('/oauth/concept-logbook', name: 'oauth_concept2_check_default')]
    public function connectCheck(ClientRegistry $clientRegistry, EntityManagerInterface $entityManager): RedirectResponse
    {
        /** @var OAuth2Client $client */
        $client = $clientRegistry->getClient('concept2');

        try {
            $accessToken = $client->getAccessToken();
            $this->getUser()->setConcept2RefreshToken($accessToken->getRefreshToken());
            $entityManager->flush();

            $this->addFlash('success', 'Votre compte Concept2 a bien été connecté.');
        } catch (IdentityProviderException) {
            $this->addFlash('error', 'Une erreur est survenue lors de la connexion de votre compte Concept2.');
        }

        return $this->redirectToRoute('sport_profile_configuration');
    }

    #[Route('/oauth/concept-logbook/unconnect', name: 'oauth_concept2_unconnect')]
    public function unconnect(EntityManagerInterface $entityManager): RedirectResponse
    {
        $this->getUser()->setConcept2RefreshToken(null);
        $entityManager->flush();

        $this->addFlash('success', 'Votre compte Concept2 a bien été déconnecté.');

        return $this->redirectToRoute('sport_profile_configuration');
    }
}
