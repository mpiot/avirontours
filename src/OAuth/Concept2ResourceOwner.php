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

use League\OAuth2\Client\Provider\ResourceOwnerInterface;

class Concept2ResourceOwner implements ResourceOwnerInterface
{
    public function __construct(private array $response)
    {
    }

    public function getId(): int
    {
        return $this->response['id'];
    }

    public function toArray(): array
    {
        return $this->response;
    }
}
