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
use App\Notification\ShellDamageNotification;
use App\Repository\LogbookEntryRepository;
use App\Repository\ShellRepository;
use App\Repository\UserRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Notifier\Notifier;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/logbook-entry')]
#[IsGranted(new Expression('is_granted("ROLE_LOGBOOK_USER") or (is_granted("ROLE_USER") and user.hasValidLicense()) or is_granted("ROLE_LOGBOOK_ADMIN")'))]
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
    public function new(Request $request, ManagerRegistry $managerRegistry): Response
    {
        $logbookEntry = new LogbookEntry();
        $form = $this->createForm(LogbookEntryStartType::class, $logbookEntry);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $managerRegistry->getManager();
            $entityManager->persist($logbookEntry);
            $entityManager->flush();

            $this->addFlash('success', 'Votre sortie a été créée avec succès.');

            return $this->redirectToRoute('logbook_entry_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('logbook_entry/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route(path: '/{id}/finish', name: 'logbook_entry_finish', methods: ['GET', 'POST'])]
    public function finish(Request $request, ManagerRegistry $managerRegistry, LogbookEntry $logbookEntry, NotifierInterface $notifier): Response
    {
        if (null !== $logbookEntry->getEndAt()) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(LogbookEntryFinishType::class, $logbookEntry);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $managerRegistry->getManager()->flush();

            // send an email to admins if there is damage
            foreach ($logbookEntry->getShellDamages() as $shellDamage) {
                /** @var Notifier $notifier */
                $notifier->send(new ShellDamageNotification($shellDamage), ...$notifier->getAdminRecipients());
            }

            $this->addFlash('success', 'Votre sortie a été terminée avec succès.');

            return $this->redirectToRoute('logbook_entry_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('logbook_entry/finish.html.twig', [
            'form' => $form,
            'logbook_entry' => $logbookEntry,
        ]);
    }

    #[Route(path: '/{id}/edit', name: 'logbook_entry_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_LOGBOOK_ADMIN')]
    public function edit(Request $request, ManagerRegistry $managerRegistry, LogbookEntry $logbookEntry): Response
    {
        $form = $this->createForm(LogbookEntryType::class, $logbookEntry);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $managerRegistry->getManager()->flush();

            $this->addFlash('success', 'La sortie a été modifiée avec succès.');

            return $this->redirectToRoute('logbook_entry_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('logbook_entry/edit.html.twig', [
            'form' => $form,
            'logbook_entry' => $logbookEntry,
        ]);
    }

    #[Route(path: '/{id}', name: 'logbook_entry_delete', methods: ['POST'])]
    #[IsGranted('ROLE_LOGBOOK_ADMIN')]
    public function delete(Request $request, ManagerRegistry $managerRegistry, LogbookEntry $logbookEntry): Response
    {
        if ($this->isCsrfTokenValid('delete'.$logbookEntry->getId(), (string) $request->request->get('_token'))) {
            $entityManager = $managerRegistry->getManager();
            $entityManager->remove($logbookEntry);
            $entityManager->flush();

            $this->addFlash('success', 'La sortie a été modifiée avec succès.');
        }

        return $this->redirectToRoute('logbook_entry_index');
    }

    #[Route(path: '/statistics', name: 'logbook_entry_statistics')]
    public function statistics(ShellRepository $shellRepository, UserRepository $userRepository): Response
    {
        return $this->render('logbook_entry/statistics.html.twig', [
            'topDistances' => $userRepository->findTop10Distances(),
            'topSessions' => $userRepository->findTop10Sessions(),
            'topShells' => $shellRepository->findTop10Sessions(),
        ]);
    }
}
