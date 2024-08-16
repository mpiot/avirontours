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
use App\Entity\TrainingPlan;
use App\Form\TrainingPlanType;
use App\Repository\PlannedTrainingRepository;
use App\Repository\TrainingPlanRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/admin/training-plan')]
#[IsGranted('ROLE_SPORT_ADMIN')]
class TrainingPlanController extends AbstractController
{
    #[Route(path: '', name: 'training_plan_index', methods: ['GET'])]
    public function index(TrainingPlanRepository $trainingPlanRepository): Response
    {
        return $this->render('admin/training_plan/index.html.twig', [
            'training_plans' => $trainingPlanRepository->findBy([], ['name' => 'ASC']),
        ]);
    }

    #[Route(path: '/new', name: 'training_plan_new', methods: ['GET', 'POST'])]
    public function new(Request $request, ManagerRegistry $managerRegistry): Response
    {
        $trainingPlan = new TrainingPlan();
        $form = $this->createForm(TrainingPlanType::class, $trainingPlan);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $managerRegistry->getManager();
            $entityManager->persist($trainingPlan);
            $entityManager->flush();

            $this->addFlash('success', 'Le plan d\'entraînement a été créé avec succès.');

            return $this->redirectToRoute('training_plan_index');
        }

        return $this->render('admin/training_plan/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route(
        path: '/{id<\d+>}',
        name: 'training_plan_show',
        methods: ['GET']
    )]
    public function show(
        TrainingPlan $trainingPlan,
        PlannedTrainingRepository $plannedTrainingRepository,
        #[MapQueryParameter] ?int $year = null,
        #[MapQueryParameter] ?int $month = null
    ): Response {
        $year ??= (int) $trainingPlan->getStartAt()->format('Y');
        $month ??= (int) $trainingPlan->getStartAt()->format('n');

        $events = [];
        $plannedTrainings = $plannedTrainingRepository->findMonthTrainings(
            $trainingPlan,
            $year,
            $month
        );
        foreach ($plannedTrainings as $plannedTraining) {
            $details = '';
            if (null !== $plannedTraining->getDuration() && null !== $plannedTraining->getDistance()) {
                $details = "{$plannedTraining->getFormattedDuration(hoursSeparator: 'h')} - {$plannedTraining->getDistance(true)} km";
            } elseif (null !== $plannedTraining->getDuration() && null === $plannedTraining->getDistance()) {
                $details = "{$plannedTraining->getFormattedDuration(hoursSeparator: 'h')}";
            } elseif (null === $plannedTraining->getDuration() && null !== $plannedTraining->getDistance()) {
                $details = "{$plannedTraining->getDistance(true)} km";
            }

            $events[] = [
                'date' => $plannedTraining->getDate()->format('Y-m-d'),
                'name' => $plannedTraining->getSport()->label(),
                'details' => $details,
                'link' => $this->generateUrl('planned_training_show', [
                    'training_plan_id' => $trainingPlan->getId(),
                    'id' => $plannedTraining->getId(),
                ]),
                'actions' => [
                    [
                        'name' => '<span class="fa-regular fa-copy" title="Dupliquer"></span>',
                        'link' => $this->generateUrl('planned_training_show', [
                            'training_plan_id' => $trainingPlan->getId(),
                            'id' => $plannedTraining->getId(),
                        ]),
                    ],
                ],
                'color' => 'secondary',
            ];
        }

        return $this->render('admin/training_plan/show.html.twig', [
            'training_plan' => $trainingPlan,
            'year' => $year,
            'month' => $month,
            'events' => $events,
        ]);
    }

    #[Route(path: '/{id<\d+>}/edit', name: 'training_plan_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ManagerRegistry $managerRegistry, TrainingPlan $trainingPlan): Response
    {
        $form = $this->createForm(TrainingPlanType::class, $trainingPlan);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $managerRegistry->getManager()->flush();

            $this->addFlash('success', 'Le plan d\'entraînement a été modifié avec succès.');

            return $this->redirectToRoute('training_plan_index');
        }

        return $this->render('admin/training_plan/edit.html.twig', [
            'form' => $form,
            'training_plan' => $trainingPlan,
        ]);
    }

    #[Route(path: '/{id<\d+>}', name: 'training_plan_delete', methods: ['POST'])]
    public function delete(Request $request, ManagerRegistry $managerRegistry, TrainingPlan $trainingPlan): Response
    {
        if ($this->isCsrfTokenValid('delete'.$trainingPlan->getId(), (string) $request->request->get('_token'))) {
            $entityManager = $managerRegistry->getManager();
            $entityManager->remove($trainingPlan);
            $entityManager->flush();

            $this->addFlash('success', 'Le plan d\'entraînement a été supprimé avec succès.');
        }

        return $this->redirectToRoute('training_plan_index');
    }
}
