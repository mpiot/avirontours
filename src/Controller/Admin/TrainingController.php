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

use App\Repository\GroupRepository;
use App\Repository\UserRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/admin/training')]
#[Security('is_granted("ROLE_SPORT_ADMIN")')]
class TrainingController extends AbstractController
{
    #[Route(path: '', name: 'admin_training_index', methods: ['GET'])]
    public function index(Request $request, UserRepository $userRepository, GroupRepository $groupRepository): Response
    {
        $from = new \DateTime($request->query->get('from') ?? '-1 month');
        $to = new \DateTime($request->query->get('to') ?? 'now');
        $group = $request->query->has('group') ? $groupRepository->find($request->query->getInt('group')) : null;

        return $this->render('admin/training/index.html.twig', [
            'users' => $userRepository->findTrainings(
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
}
