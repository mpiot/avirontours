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

namespace App\Tests\Validator;

use App\Validator\Turnstile;
use App\Validator\TurnstileValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class TurnstileValidatorTest extends TestCase
{
    public function testValidWhenChallengeSucceeds(): void
    {
        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects(self::never())->method('buildViolation');

        $this->validate($context, token: 'a-token', apiResponse: ['success' => true]);
    }

    public function testInvalidWhenChallengeFails(): void
    {
        $this->expectViolation((new Turnstile())->message, token: 'a-token', apiResponse: ['success' => false]);
    }

    public function testInvalidWhenResponseHasNoSuccessKey(): void
    {
        $this->expectViolation((new Turnstile())->message, token: 'a-token', apiResponse: []);
    }

    public function testInvalidWhenNoTokenIsSubmitted(): void
    {
        $this->expectViolation((new Turnstile())->noResponseMessage, token: null, apiResponse: null);
    }

    /**
     * @param array<string, mixed>|null $apiResponse
     */
    private function expectViolation(string $message, ?string $token, ?array $apiResponse): void
    {
        $builder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $builder->expects(self::once())->method('addViolation');

        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects(self::once())->method('buildViolation')->with($message)->willReturn($builder);

        $this->validate($context, $token, $apiResponse);
    }

    /**
     * @param array<string, mixed>|null $apiResponse
     */
    private function validate(ExecutionContextInterface $context, ?string $token, ?array $apiResponse): void
    {
        $requestStack = new RequestStack();
        $requestStack->push(Request::create('/register', 'POST', null === $token ? [] : ['cf-turnstile-response' => $token]));

        $httpClient = new MockHttpClient(null === $apiResponse ? [] : [new JsonMockResponse($apiResponse)]);

        $validator = new TurnstileValidator('prod', 'secret-key', $requestStack, $httpClient);
        $validator->validateInContext('value', new Turnstile(), $context);
    }
}
