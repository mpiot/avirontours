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

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class HomepageController extends AbstractController
{
    #[Route(path: '/admin', name: 'admin_home')]
    #[Security('is_granted("ROLE_MATERIAL_ADMIN") or is_granted("ROLE_SPORT_ADMIN") or is_granted("ROLE_USER_ADMIN")')]
    public function homepage()
    {
        return $this->render('admin/homepage/homepage.twig');
    }
}
