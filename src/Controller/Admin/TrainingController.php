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

use App\Chart\TrainingChart;
use App\Controller\AbstractController;
use App\Entity\Training;
use App\Entity\User;
use App\Repository\GroupRepository;
use App\Repository\TrainingRepository;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/admin/training')]
#[IsGranted('ROLE_SPORT_ADMIN')]
class TrainingController extends AbstractController
{
    #[Route(path: '', name: 'admin_training_index', methods: ['GET'])]
    public function index(Request $request, UserRepository $userRepository, GroupRepository $groupRepository): Response
    {
        $from = new \DateTime($request->query->get('from') ?? '-1 month');
        $to = new \DateTime($request->query->get('to') ?? 'now');
        $group = $request->query->has('group') ? $groupRepository->find($request->query->getInt('group')) : null;

        return $this->render('admin/training/index.html.twig', [
            'users' => $userRepository->findUsersTrainings(
                $from,
                $to,
                $group,
                $request->query->get('q'),
                $request->query->getInt('page', 1)
            ),
            'from' => $from,
            'to' => $to,
            'group' => $group,
            'groups' => $groupRepository->findBy([], ['name' => 'asc']),
        ]);
    }

    #[Route(path: '/{user_id}', name: 'admin_training_list', methods: ['GET'])]
    public function list(
        Request $request,
        #[MapEntity(mapping: ['user_id' => 'id'])] User $user,
        TrainingRepository $trainingRepository,
        TrainingChart $trainingsChart
    ): Response {
        return $this->render('admin/training/list.html.twig', [
            'user' => $user,
            'trainings' => $trainingRepository->findUserPaginated($user, $request->query->getInt('page', 1)),
            'trainingsPathwaysChart' => $trainingsChart->pathways($user),
            'trainingsSportsChart' => $trainingsChart->sports($user),
        ]);
    }

    #[Route(path: '/{user_id}/{id<\d+>}', name: 'admin_training_show', methods: ['GET'])]
    public function show(
        #[MapEntity(mapping: ['user_id' => 'id'])] User $user,
        Training $training
    ): Response {
        return $this->render('admin/training/show.html.twig', [
            'user' => $user,
            'training' => $training,
        ]);
    }
}
