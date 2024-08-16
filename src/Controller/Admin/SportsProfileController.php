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
use App\Entity\Anatomy;
use App\Entity\PhysicalQualities;
use App\Entity\Physiology;
use App\Entity\User;
use App\Entity\WorkoutMaximumLoad;
use App\Form\AnatomyType;
use App\Form\PhysicalQualitiesType;
use App\Form\PhysiologyType;
use App\Form\WorkoutMaximumLoadType;
use App\Repository\UserRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/admin/sports-profile')]
#[IsGranted('ROLE_SPORT_ADMIN')]
class SportsProfileController extends AbstractController
{
    #[Route(path: '', name: 'sports_profile_index', methods: ['GET'])]
    public function index(Request $request, UserRepository $userRepository): Response
    {
        return $this->render('admin/sports_profile/index.html.twig', [
            'users' => $userRepository->findPaginated(
                $request->query->get('q'),
                $request->query->getInt('page', 1)
            ),
        ]);
    }

    #[Route(path: '/{id<\d+>}/physiology', name: 'sports_profile_physiology', methods: ['GET', 'POST'])]
    public function physiology(Request $request, ManagerRegistry $managerRegistry, User $user): Response
    {
        $physiology = $user->getPhysiology() ?? new Physiology($user);
        $form = $this->createForm(PhysiologyType::class, $physiology);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $managerRegistry->getManager()->flush();

            $this->addFlash('success', 'La physiologie a été modifiée avec succès.');

            return $this->redirectToRoute('sports_profile_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/sports_profile/physiology.html.twig', [
            'form' => $form,
            'user' => $user,
        ]);
    }

    #[Route(path: '/{id<\d+>}/anatomy', name: 'sports_profile_anatomy', methods: ['GET', 'POST'])]
    public function anatomy(Request $request, ManagerRegistry $managerRegistry, User $user): Response
    {
        $anatomy = $user->getAnatomy() ?? new Anatomy($user);
        $form = $this->createForm(AnatomyType::class, $anatomy);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $managerRegistry->getManager()->flush();

            $this->addFlash('success', 'L\'anatomie a été modifiée avec succès.');

            return $this->redirectToRoute('sports_profile_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/sports_profile/anatomy.html.twig', [
            'form' => $form,
            'user' => $user,
        ]);
    }

    #[Route(path: '/{id<\d+>}/physical-qualities', name: 'sports_profile_physical_qualities', methods: ['GET', 'POST'])]
    public function physicalQualities(Request $request, ManagerRegistry $managerRegistry, User $user): Response
    {
        $physicalQualities = $user->getPhysicalQualities() ?? new PhysicalQualities($user);
        $form = $this->createForm(PhysicalQualitiesType::class, $physicalQualities);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $managerRegistry->getManager()->flush();

            $this->addFlash('success', 'Les qualités physiques ont été modifiées avec succès.');

            return $this->redirectToRoute('sports_profile_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/sports_profile/physical_qualities.html.twig', [
            'form' => $form,
            'user' => $user,
        ]);
    }

    #[Route(path: '/{id<\d+>}/workout-maximum-load', name: 'sports_profile_workout_maximum_load', methods: ['GET', 'POST'])]
    public function workoutMaximumLoad(Request $request, ManagerRegistry $managerRegistry, User $user): Response
    {
        $workoutMaximumLoad = $user->getWorkoutMaximumLoad() ?? new WorkoutMaximumLoad($user);
        $form = $this->createForm(WorkoutMaximumLoadType::class, $workoutMaximumLoad);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $managerRegistry->getManager()->flush();

            $this->addFlash('success', 'Les 1RM on été modifiées avec succès.');

            return $this->redirectToRoute('sports_profile_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/sports_profile/workout_maximum_load.html.twig', [
            'form' => $form,
            'user' => $user,
        ]);
    }
}
