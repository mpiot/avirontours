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

namespace App\Validator;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class TurnstileValidator extends ConstraintValidator
{
    public function __construct(
        private readonly string $env,
        private readonly string $turnstileSecretKey,
        private readonly RequestStack $requestStack,
        private readonly HttpClientInterface $httpClient
    ) {
    }

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof Turnstile) {
            throw new UnexpectedTypeException($constraint, Turnstile::class);
        }

        if ('test' === $this->env) {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();
        $turnstileResponse = $request->request->get('cf-turnstile-response');

        if (empty($turnstileResponse)) {
            $this->context->buildViolation($constraint->noResponseMessage)->addviolation();

            return;
        }

        $response = $this->httpClient->request(
            'POST',
            'https://challenges.cloudflare.com/turnstile/v0/siteverify',
            [
                'body' => [
                    'response' => $turnstileResponse,
                    'secret' => $this->turnstileSecretKey,
                ],
            ]
        );
        $content = $response->toArray();

        if (false === $content['success']) {
            $this->context->buildViolation($constraint->message)->addviolation();
        }
    }
}
