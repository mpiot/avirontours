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

use App\Entity\LogbookEntry;
use App\Form\LogbookEntryFinishType;
use App\Form\LogbookEntryNewType;
use App\Form\LogbookEntryType;
use App\Notification\ShellDamageNotification;
use App\Repository\LogbookEntryRepository;
use App\Repository\MemberRepository;
use App\Repository\ShellRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/logbook-entry")
 * @Security("is_granted('ROLE_USER')")
 */
class LogbookEntryController extends AbstractController
{
    /**
     * @Route("/", name="logbook_entry_index", methods={"GET"})
     */
    public function index(Request $request, LogbookEntryRepository $logbookEntryRepository): Response
    {
        return $this->render('logbook_entry/index.html.twig', [
            'logbook_entries' => $logbookEntryRepository->findAllPaginated($request->query->getInt('page', 1)),
        ]);
    }

    /**
     * @Route("/new", name="logbook_entry_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $logbookEntry = new LogbookEntry();
        $form = $this->createForm(LogbookEntryNewType::class, $logbookEntry);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($logbookEntry);
            $entityManager->flush();

            return $this->redirectToRoute('logbook_entry_index');
        }

        return $this->render('logbook_entry/new.html.twig', [
            'logbook_entry' => $logbookEntry,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/finish", name="logbook_entry_finish", methods={"GET","POST"})
     */
    public function finish(Request $request, LogbookEntry $logbookEntry, NotifierInterface $notifier): Response
    {
        if (null !== $logbookEntry->getEndAt()) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(LogbookEntryFinishType::class, $logbookEntry);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // update the shell mileage
            $logbookEntry->getShell()->addToMileage($logbookEntry->getCoveredDistance());
            $this->getDoctrine()->getManager()->flush();

            // send an email to admins if there is damage
            foreach ($logbookEntry->getShellDamages() as $shellDamage) {
                $notifier->send(new ShellDamageNotification($shellDamage), ...$notifier->getAdminRecipients());
            }

            return $this->redirectToRoute('logbook_entry_index');
        }

        return $this->render('logbook_entry/finish.html.twig', [
            'logbook_entry' => $logbookEntry,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/edit", name="logbook_entry_edit", methods={"GET","POST"})
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function edit(Request $request, LogbookEntry $logbookEntry): Response
    {
        $coveredDistance = $logbookEntry->getCoveredDistance();
        $form = $this->createForm(LogbookEntryType::class, $logbookEntry);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // update the shell mileage
            $logbookEntry->getShell()->removeToMileage($coveredDistance);
            $logbookEntry->getShell()->addToMileage($logbookEntry->getCoveredDistance());
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('logbook_entry_index');
        }

        return $this->render('logbook_entry/edit.html.twig', [
            'logbook_entry' => $logbookEntry,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="logbook_entry_delete", methods={"DELETE"})
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function delete(Request $request, LogbookEntry $logbookEntry): Response
    {
        if ($this->isCsrfTokenValid('delete'.$logbookEntry->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();

            // update the shell mileage
            if (null !== $distance = $logbookEntry->getCoveredDistance()) {
                $logbookEntry->getShell()->removeToMileage($distance);
            }

            $entityManager->remove($logbookEntry);
            $entityManager->flush();
        }

        return $this->redirectToRoute('logbook_entry_index');
    }

    /**
     * @Route("/statistics", name="logbook_entry_statistics")
     * @Security("is_granted('ROLE_USER')")
     */
    public function statistics(ShellRepository $shellRepository, MemberRepository $memberRepository)
    {
        return $this->render('logbook_entry/statistics.html.twig', [
            'topDistances' => $memberRepository->findTop10Distances(),
            'topSessions' => $memberRepository->findTop10Sessions(),
            'topShells' => $shellRepository->findTop10Sessions(),
        ]);
    }
}
