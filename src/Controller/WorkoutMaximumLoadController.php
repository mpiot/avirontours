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

use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/workout-maximum-load')]
#[IsGranted(new Expression('(is_granted("ROLE_USER") and user.hasValidLicense()) or is_granted("ROLE_ADMIN")'))]
class WorkoutMaximumLoadController extends AbstractController
{
    #[Route(path: '', name: 'workout_maximum_load_show', methods: ['GET'])]
    public function show(): Response
    {
        return $this->render('workout_maximum_load/show.html.twig');
    }
}
