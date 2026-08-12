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

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class TurboFrameRedirectSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly UrlGeneratorInterface $urlGenerator)
    {
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (false === $event->isMainRequest()) {
            return;
        }

        if (false === $this->shouldWrapRedirect($event->getRequest(), $event->getResponse())) {
            return;
        }

        $event->setResponse(new Response(null, Response::HTTP_OK, [
            'Turbo-Location' => $event->getResponse()->headers->get('Location'),
        ]));
    }

    private function shouldWrapRedirect(Request $request, Response $response): bool
    {
        if (false === $response->isRedirection()) {
            return false;
        }

        if (false === $request->headers->has('Turbo-Frame')) {
            return false;
        }

        $location = $response->headers->get('Location');
        if (null === $location) {
            return false;
        }

        if ($location === $this->urlGenerator->generate('app_login')) {
            return true;
        }

        return $request->headers->has('Turbo-Frame-Redirect');
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }
}
