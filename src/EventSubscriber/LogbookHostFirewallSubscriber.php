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
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use function Symfony\Component\String\u;

class LogbookHostFirewallSubscriber implements EventSubscriberInterface
{
    private $logbookDomain;
    private $router;

    public function __construct(string $logbookDomain, RouterInterface $router)
    {
        $this->logbookDomain = $logbookDomain;
        $this->router = $router;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if ($this->logbookDomain !== $event->getRequest()->getHost()) {
            return;
        }

        $logbookPath = $this->router->generate('logbook_entry_index');
        if (false === u($event->getRequest()->getPathInfo())->startsWith($logbookPath)) {
            $event->setResponse(new RedirectResponse($this->router->generate('logbook_entry_index', [], UrlGeneratorInterface::ABSOLUTE_PATH)));
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            'kernel.request' => 'onKernelRequest',
        ];
    }
}
