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

use App\Chart\TrainingPhaseChart;
use App\Entity\Training;
use App\Entity\TrainingPhase;
use App\Form\TrainingType;
use App\Message\Concept2ImportMessage;
use App\Repository\TrainingRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/training')]
#[IsGranted(new Expression('(is_granted("ROLE_USER") and user.hasValidLicense()) or is_granted("ROLE_ADMIN")'))]
class TrainingController extends AbstractController
{
    #[Route(path: '', name: 'training_index', methods: ['GET'])]
    public function index(
        TrainingRepository $trainingRepository,
        #[MapQueryParameter(filter: \FILTER_VALIDATE_REGEXP, options: ['regexp' => '#^\d{4}-\d{2}-\d{2}$#'])] ?string $endAt = null,
    ): Response {
        $endAt = null !== $endAt ? new \DateTimeImmutable($endAt) : new \DateTimeImmutable('now');
        $endAt = $endAt->modify('sunday this week')->setTime(0, 0);

        $startAt = $endAt->modify('-1 month')->modify('monday this week');

        $weeks = new \DatePeriod(
            $startAt,
            new \DateInterval('P1W'),
            $endAt,
            \DatePeriod::INCLUDE_END_DATE
        );

        return $this->render('training/index.html.twig', [
            'startAt' => $startAt,
            'endAt' => $endAt,
            'weeks' => $weeks,
            'trainings' => $trainingRepository->findForUser($this->getUser(), $startAt, $endAt),
        ]);
    }

    #[Route(path: '/new', name: 'training_new', methods: ['GET', 'POST'])]
    public function new(Request $request, ManagerRegistry $managerRegistry): Response
    {
        $training = new Training($this->getUser());
        $form = $this->createForm(TrainingType::class, $training);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $managerRegistry->getManager();
            $entityManager->persist($training);
            $entityManager->flush();

            $this->addFlash('success', 'Votre entraînement a été créé avec succès.');

            return $this->redirectToRoute('training_index');
        }

        return $this->render('training/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route(path: '/import/concept-logbook', name: 'training_import_concept_logbook')]
    public function importConceptLogbook(MessageBusInterface $bus): Response
    {
        $bus->dispatch(new Concept2ImportMessage($this->getUser()->getId()));

        $this->addFlash('success', 'Vos entraînements sont en cours de synchronisation.');

        return $this->redirectToRoute('training_index');
    }

    #[Route(path: '/{id}', name: 'training_show', methods: ['GET'])]
    #[IsGranted(new Expression('object.getUser() === user'), 'training')]
    public function show(Training $training): Response
    {
        return $this->render('training/show.html.twig', [
            'training' => $training,
        ]);
    }

    #[Route(path: '/{training_id}/phase/{id}', name: 'training_show_phase', methods: ['GET'])]
    #[IsGranted(new Expression('object.getUser() === user or is_granted("ROLE_SPORT_ADMIN")'), 'training')]
    public function showPhase(
        #[MapEntity(mapping: ['training_id' => 'id'])] Training $training,
        TrainingPhase $trainingPhase,
        TrainingPhaseChart $trainingPhaseChart,
    ): Response {
        return $this->render('training/_phase.html.twig', [
            'training' => $training,
            'active_phase' => $trainingPhase,
            'chart' => $trainingPhaseChart->chart($trainingPhase),
        ]);
    }

    #[Route(path: '/{id}/edit', name: 'training_edit', methods: ['GET', 'POST'])]
    #[IsGranted(new Expression('object.getUser() === user'), 'training')]
    public function edit(Request $request, ManagerRegistry $managerRegistry, Training $training): Response
    {
        $form = $this->createForm(TrainingType::class, $training);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $managerRegistry->getManager()->flush();

            $this->addFlash('success', 'Votre entraînement a été modifié avec succès.');

            return $this->redirectToRoute('training_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('training/edit.html.twig', [
            'form' => $form,
            'training' => $training,
        ]);
    }

    #[Route(path: '/{id}', name: 'training_delete', methods: ['POST'])]
    #[IsGranted(new Expression('object.getUser() === user'), 'training')]
    public function delete(Request $request, ManagerRegistry $managerRegistry, Training $training): Response
    {
        if ($this->isCsrfTokenValid('delete'.$training->getId(), (string) $request->request->get('_token'))) {
            $entityManager = $managerRegistry->getManager();
            $entityManager->remove($training);
            $entityManager->flush();

            $this->addFlash('success', 'Votre entraînement a été supprimé avec succès.');
        }

        return $this->redirectToRoute('training_index');
    }
}
