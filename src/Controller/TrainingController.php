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

use App\Entity\Training;
use App\Entity\TrainingPhase;
use App\Form\TrainingType;
use App\Message\Concept2ImportMessage;
use App\Repository\TrainingRepository;
use App\Util\DurationManipulator;
use Doctrine\Persistence\ManagerRegistry;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

#[Route(path: '/training')]
#[Security('(is_granted("ROLE_USER") and user.hasValidLicense()) or is_granted("ROLE_ADMIN")')]
class TrainingController extends AbstractController
{
    #[Route(path: '', name: 'training_index', methods: ['GET'])]
    public function index(Request $request, TrainingRepository $trainingRepository): Response
    {
        return $this->render('training/index.html.twig', [
            'trainings' => $trainingRepository->findUserPaginated($this->getUser(), $request->query->getInt('page', 1)),
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

        return $this->renderForm('training/new.html.twig', [
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
    #[Security('training.getUser() == user')]
    public function show(Training $training, ChartBuilderInterface $chartBuilder): Response
    {
        return $this->render('training/show.html.twig', [
            'training' => $training,
        ]);
    }

    #[Route(path: '/{training_id}/phase/{id}', name: 'training_show_phase', methods: ['GET'])]
    #[Entity('training', expr: 'repository.find(training_id)')]
    #[Security('training.getUser() == user or is_granted("ROLE_SPORT_ADMIN")')]
    public function showPhase(Training $training, TrainingPhase $trainingPhase, ChartBuilderInterface $chartBuilder): Response
    {
        $datasets = [
            [
                'label' => 'Pace',
                'yAxisID' => 'pace',
                'data' => array_map(fn (int $tenthSecondsPer500) => (int) round($tenthSecondsPer500 / 10), $trainingPhase->getPaces()),
                'borderColor' => 'rgb(124,181,236, 1)',
            ],
            [
                'label' => 'SPM',
                'yAxisID' => 'spm',
                'data' => $trainingPhase->getStrokeRates(),
                'borderColor' => 'rgba(67, 67, 72, 1)',
            ],
        ];
        $scales = [
            'x' => [
                'grid' => [
                    'display' => false,
                ],
            ],
            'pace' => [
                'type' => 'linear',
                'position' => 'left',
                'min' => 90,
                'max' => 240,
                'reverse' => true,
                'title' => [
                    'display' => true,
                    'text' => 'Pace',
                ],
                'ticks' => [
                    'precision' => 0,
                    'count' => 6,
                ],
            ],
            'spm' => [
                'type' => 'linear',
                'position' => 'right',
                'title' => [
                    'display' => true,
                    'text' => 'Stroke Rate',
                ],
                'ticks' => [
                    'precision' => 0,
                    'count' => 6,
                    'stepSize' => 10,
                ],
            ],
        ];
        if (null !== $trainingPhase->getHeartRates()) {
            $datasets[] = [
                'label' => 'Heart rate',
                'yAxisID' => 'hr',
                'data' => $trainingPhase->getHeartRates(),
                'borderColor' => 'rgb(255,0,0, 1)',
            ];

            $scales['hr'] = [
                'type' => 'linear',
                'display' => false,
                'position' => 'right',
                'min' => 25,
                'title' => [
                    'display' => true,
                    'text' => 'Heart Rate',
                ],
                'ticks' => [
                    'precision' => 0,
                    'count' => 6,
                    'stepSize' => 25,
                ],
            ];
        }

        $chart = $chartBuilder->createChart(Chart::TYPE_LINE);
        $chart->setData([
            'labels' => array_map(fn (int $tenthSeconds) => DurationManipulator::formatSeconds((int) round($tenthSeconds / 10)), $trainingPhase->getTimes()),
            'datasets' => $datasets,
        ]);
        $chart->setOptions([
            'datasets' => [
                'line' => [
                    'borderWidth' => 1,
                    'pointRadius' => 0,
                ],
            ],
            'plugins' => [
                'tooltip' => [
                    'intersect' => false,
                    'mode' => 'index',
                ],
            ],
            'scales' => $scales,
        ]);

        return $this->render('training/_phase.html.twig', [
            'training' => $training,
            'active_phase' => $trainingPhase,
            'chart' => $chart,
        ]);
    }

    #[Route(path: '/{id}/edit', name: 'training_edit', methods: ['GET', 'POST'])]
    #[Security('training.getUser() == user')]
    public function edit(Request $request, ManagerRegistry $managerRegistry, Training $training): Response
    {
        $form = $this->createForm(TrainingType::class, $training);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $managerRegistry->getManager()->flush();

            $this->addFlash('success', 'Votre entraînement a été modifié avec succès.');

            return $this->redirectToRoute('training_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('training/edit.html.twig', [
            'form' => $form,
            'training' => $training,
        ]);
    }

    #[Route(path: '/{id}', name: 'training_delete', methods: ['POST'])]
    #[Security('training.getUser() == user')]
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
