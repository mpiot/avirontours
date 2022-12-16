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

namespace App\OAuth;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Token\AccessToken;
use Psr\Http\Message\ResponseInterface;

class Concept2Provider extends AbstractProvider
{
    public const SCOPES = ['user:read', 'results:read'];

    public function getBaseAuthorizationUrl(): string
    {
        return 'https://log.concept2.com/oauth/authorize';
    }

    public function getBaseAccessTokenUrl(array $params): string
    {
        return 'https://log.concept2.com/oauth/access_token';
    }

    public function getResourceOwnerDetailsUrl(AccessToken $token): string
    {
        return 'https://log.concept2.com/api/users/me';
    }

    public function getAccessToken($grant, array $options = [])
    {
        $scopeString = implode(',', self::SCOPES);

        return parent::getAccessToken($grant, array_merge(['scope' => $scopeString], $options));
    }

    protected function getDefaultScopes(): array
    {
        return self::SCOPES;
    }

    protected function checkResponse(ResponseInterface $response, $data): void
    {
        if ($response->getStatusCode() >= 400) {
            throw new IdentityProviderException($data['message'] ?: $response->getReasonPhrase(), $response->getStatusCode(), (string) $response->getBody());
        }
        if (isset($data['error'])) {
            throw new IdentityProviderException($data['error'] ?: $response->getReasonPhrase(), $response->getStatusCode(), (string) $response->getBody());
        }
    }

    protected function createResourceOwner(array $response, AccessToken $token): ResourceOwnerInterface
    {
        return new Concept2ResourceOwner($response);
    }
}
