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

use App\Entity\LogbookEntry;
use App\Form\LogbookEntryFinishType;
use App\Form\LogbookEntryStartType;
use App\Form\LogbookEntryType;
use App\Repository\LogbookEntryRepository;
use App\Repository\ShellRepository;
use App\Repository\UserRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/logbook-entry')]
#[Security('is_granted("ROLE_LOGBOOK_USER") or (is_granted("ROLE_USER") and user.hasValidLicense()) or is_granted("ROLE_LOGBOOK_ADMIN")')]
class LogbookEntryController extends AbstractController
{
    #[Route(path: '', name: 'logbook_entry_index', methods: ['GET'])]
    public function index(Request $request, LogbookEntryRepository $logbookEntryRepository): Response
    {
        return $this->render('logbook_entry/index.html.twig', [
            'logbook_entries' => $logbookEntryRepository->findAllPaginated($request->query->getInt('page', 1)),
        ]);
    }

    #[Route(path: '/new', name: 'logbook_entry_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $logbookEntry = new LogbookEntry();

        return $this->handleForm(
            $this->createForm(LogbookEntryStartType::class, $logbookEntry),
            $request,
            function () use ($logbookEntry) {
                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($logbookEntry);
                $entityManager->flush();

                $this->addFlash('success', 'Votre sortie a été créée avec succès.');

                return $this->redirectToRoute('logbook_entry_index', [], Response::HTTP_SEE_OTHER);
            },
            function (FormInterface $form) use ($logbookEntry) {
                return $this->render('logbook_entry/new.html.twig', [
                    'logbook_entry' => $logbookEntry,
                    'form' => $form->createView(),
                ]);
            }
        );
    }

    #[Route(path: '/{id}/finish', name: 'logbook_entry_finish', methods: ['GET', 'POST'])]
    public function finish(Request $request, LogbookEntry $logbookEntry, NotifierInterface $notifier): Response
    {
        if (null !== $logbookEntry->getEndAt()) {
            throw $this->createNotFoundException();
        }

        return $this->handleForm(
            $this->createForm(LogbookEntryFinishType::class, $logbookEntry),
            $request,
            function () {
                $this->getDoctrine()->getManager()->flush();

                $this->addFlash('success', 'Votre sortie a été terminée avec succès.');

                return $this->redirectToRoute('logbook_entry_index', [], Response::HTTP_SEE_OTHER);
            },
            function (FormInterface $form) use ($logbookEntry) {
                return $this->render('logbook_entry/finish.html.twig', [
                    'logbook_entry' => $logbookEntry,
                    'form' => $form->createView(),
                ]);
            }
        );
    }

    #[Route(path: '/{id}/edit', name: 'logbook_entry_edit', methods: ['GET', 'POST'])]
    #[Security('is_granted("ROLE_LOGBOOK_ADMIN")')]
    public function edit(Request $request, LogbookEntry $logbookEntry): Response
    {
        return $this->handleForm(
            $this->createForm(LogbookEntryType::class, $logbookEntry),
            $request,
            function () {
                $this->getDoctrine()->getManager()->flush();

                $this->addFlash('success', 'La sortie a été modifiée avec succès.');

                return $this->redirectToRoute('logbook_entry_index', [], Response::HTTP_SEE_OTHER);
            },
            function (FormInterface $form) use ($logbookEntry) {
                return $this->render('logbook_entry/edit.html.twig', [
                    'logbook_entry' => $logbookEntry,
                    'form' => $form->createView(),
                ]);
            }
        );
    }

    #[Route(path: '/{id}', name: 'logbook_entry_delete', methods: ['DELETE'])]
    #[Security('is_granted("ROLE_LOGBOOK_ADMIN")')]
    public function delete(Request $request, LogbookEntry $logbookEntry): Response
    {
        if ($this->isCsrfTokenValid('delete'.$logbookEntry->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($logbookEntry);
            $entityManager->flush();

            $this->addFlash('success', 'La sortie a été modifiée avec succès.');
        }

        return $this->redirectToRoute('logbook_entry_index');
    }

    #[Route(path: '/statistics', name: 'logbook_entry_statistics')]
    public function statistics(ShellRepository $shellRepository, UserRepository $userRepository)
    {
        return $this->render('logbook_entry/statistics.html.twig', [
            'topDistances' => $userRepository->findTop10Distances(),
            'topSessions' => $userRepository->findTop10Sessions(),
            'topShells' => $shellRepository->findTop10Sessions(),
        ]);
    }
}
