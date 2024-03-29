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
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(new Expression('is_granted("ROLE_MATERIAL_ADMIN") or is_granted("ROLE_SPORT_ADMIN") or is_granted("ROLE_USER_ADMIN") or is_granted("ROLE_SEASON_MEDICAL_CERTIFICATE_ADMIN") or is_granted("ROLE_SEASON_PAYMENTS_ADMIN")'))]
class HomepageController extends AbstractController
{
    #[Route(path: '/admin', name: 'admin_home')]
    public function homepage(): Response
    {
        return $this->render('admin/homepage/homepage.twig');
    }
}
