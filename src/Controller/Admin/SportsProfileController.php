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
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/admin/sports-profile')]
#[Security('is_granted("ROLE_SPORT_ADMIN")')]
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

    #[Route(path: '/{id}/physiology', name: 'sports_profile_physiology', methods: ['GET', 'POST'])]
    public function physiology(Request $request, User $user): Response
    {
        $physiology = $user->getPhysiology() ?? new Physiology($user);

        return $this->handleForm(
            $this->createForm(PhysiologyType::class, $physiology),
            $request,
            function () {
                $this->getDoctrine()->getManager()->flush();

                $this->addFlash('success', 'La physiologie a été modifiée avec succès.');

                return $this->redirectToRoute('sports_profile_index', [], Response::HTTP_SEE_OTHER);
            },
            function (FormInterface $form) use ($user) {
                return $this->render('admin/sports_profile/physiology.html.twig', [
                    'user' => $user,
                    'form' => $form->createView(),
                ]);
            }
        );
    }

    #[Route(path: '/{id}/anatomy', name: 'sports_profile_anatomy', methods: ['GET', 'POST'])]
    public function anatomy(Request $request, User $user): Response
    {
        $anatomy = $user->getAnatomy() ?? new Anatomy($user);

        return $this->handleForm(
            $this->createForm(AnatomyType::class, $anatomy),
            $request,
            function () {
                $this->getDoctrine()->getManager()->flush();

                $this->addFlash('success', 'L\'anatomie a été modifiée avec succès.');

                return $this->redirectToRoute('sports_profile_index', [], Response::HTTP_SEE_OTHER);
            },
            function (FormInterface $form) use ($user) {
                return $this->render('admin/sports_profile/anatomy.html.twig', [
                    'user' => $user,
                    'form' => $form->createView(),
                ]);
            }
        );
    }

    #[Route(path: '/{id}/physical-qualities', name: 'sports_profile_physical_qualities', methods: ['GET', 'POST'])]
    public function physicalQualities(Request $request, User $user): Response
    {
        $physicalQualities = $user->getPhysicalQualities() ?? new PhysicalQualities($user);

        return $this->handleForm(
            $this->createForm(PhysicalQualitiesType::class, $physicalQualities),
            $request,
            function () {
                $this->getDoctrine()->getManager()->flush();

                $this->addFlash('success', 'Les qualités physiques ont été modifiées avec succès.');

                return $this->redirectToRoute('sports_profile_index', [], Response::HTTP_SEE_OTHER);
            },
            function (FormInterface $form) use ($user) {
                return $this->render('admin/sports_profile/physical_qualities.html.twig', [
                    'user' => $user,
                    'form' => $form->createView(),
                ]);
            }
        );
    }

    #[Route(path: '/{id}/workout-maximum-load', name: 'sports_profile_workout_maximum_load', methods: ['GET', 'POST'])]
    public function workoutMaximumLoad(Request $request, User $user): Response
    {
        $workoutMaximumLoad = $user->getWorkoutMaximumLoad() ?? new WorkoutMaximumLoad($user);

        return $this->handleForm(
            $this->createForm(WorkoutMaximumLoadType::class, $workoutMaximumLoad),
            $request,
            function () {
                $this->getDoctrine()->getManager()->flush();

                $this->addFlash('success', 'Les 1RM on été modifiées avec succès.');

                return $this->redirectToRoute('sports_profile_index', [], Response::HTTP_SEE_OTHER);
            },
            function (FormInterface $form) use ($user) {
                return $this->render('admin/sports_profile/workout_maximum_load.html.twig', [
                    'user' => $user,
                    'form' => $form->createView(),
                ]);
            }
        );
    }
}
