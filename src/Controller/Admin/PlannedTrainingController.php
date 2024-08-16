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

namespace App\Controller\Admin;

use App\Controller\AbstractController;
use App\Entity\PlannedTraining;
use App\Entity\TrainingPlan;
use App\Form\PlannedTrainingType;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/admin/training-plan/{training_plan_id}')]
#[IsGranted('ROLE_SPORT_ADMIN')]
class PlannedTrainingController extends AbstractController
{
    #[Route(path: '/new', name: 'planned_training_new', methods: ['GET', 'POST'])]
    public function new(
        #[MapEntity(mapping: ['training_plan_id' => 'id'])] TrainingPlan $trainingPlan,
        #[MapQueryParameter] ?string $date,
        Request $request,
        ManagerRegistry $managerRegistry
    ): Response {
        $date = new \DateTimeImmutable($date ?? 'now');
        $plannedTraining = new PlannedTraining($trainingPlan, $date);
        $form = $this->createForm(PlannedTrainingType::class, $plannedTraining);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $managerRegistry->getManager();
            $entityManager->persist($plannedTraining);
            $entityManager->flush();

            $this->addFlash('success', 'Le plan d\'entraînement a été créé avec succès.');

            return $this->redirectToRoute('training_plan_show', [
                'id' => $trainingPlan->getId(),
                'year' => $date->format('Y'),
                'month' => $date->format('n'),
            ]);
        }

        return $this->render('admin/planned_training/new.html.twig', [
            'form' => $form,
            'training_plan' => $trainingPlan,
        ]);
    }

    #[Route(path: '/{id<\d+>}', name: 'planned_training_show', methods: ['GET'])]
    public function show(
        PlannedTraining $plannedTraining,
        #[MapQueryParameter] ?int $year = null,
        #[MapQueryParameter] ?int $month = null
    ): Response {
        return $this->render('admin/planned_training/show.html.twig', [
            'planned_training' => $plannedTraining,
            'year' => $year ?? $plannedTraining->getStartAt()->format('Y'),
            'month' => $month ?? $plannedTraining->getStartAt()->format('n'),
        ]);
    }

    #[Route(path: '/{id<\d+>}/edit', name: 'planned_training_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ManagerRegistry $managerRegistry, PlannedTraining $plannedTraining): Response
    {
        $form = $this->createForm(PlannedTrainingType::class, $plannedTraining);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $managerRegistry->getManager()->flush();

            $this->addFlash('success', 'Le plan d\'entraînement a été modifié avec succès.');

            return $this->redirectToRoute('planned_training_index');
        }

        return $this->render('admin/planned_training/edit.html.twig', [
            'form' => $form,
            'planned_training' => $plannedTraining,
        ]);
    }

    #[Route(path: '/{id<\d+>}', name: 'planned_training_delete', methods: ['POST'])]
    public function delete(Request $request, ManagerRegistry $managerRegistry, PlannedTraining $plannedTraining): Response
    {
        if ($this->isCsrfTokenValid('delete'.$plannedTraining->getId(), (string) $request->request->get('_token'))) {
            $entityManager = $managerRegistry->getManager();
            $entityManager->remove($plannedTraining);
            $entityManager->flush();

            $this->addFlash('success', 'Le plan d\'entraînement a été supprimé avec succès.');
        }

        return $this->redirectToRoute('training_plan_index');
    }
}
