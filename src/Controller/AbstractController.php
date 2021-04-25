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

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractController extends \Symfony\Bundle\FrameworkBundle\Controller\AbstractController
{
    /**
     * Handles a form.
     *
     * * if the form is not submitted, $render is called
     * * if the form is submitted but invalid, $render is called and a 422 HTTP status code is set if the current status hasn't been customized
     * * if the form is submitted and valid, $onSuccess is called, usually this method saves the data and returns a 303 HTTP redirection
     *
     * @param callable(FormInterface, mixed): Response $onSuccess
     * @param callable(FormInterface, mixed): Response $render
     */
    public function handleForm(FormInterface $form, Request $request, callable $onSuccess, callable $render): Response
    {
        $form->handleRequest($request);

        $submitted = $form->isSubmitted();

        $data = $form->getData();
        if ($submitted && $form->isValid()) {
            return $onSuccess($form, $data);
        }

        $response = $render($form, $data);
        if ($submitted && 200 === $response->getStatusCode()) {
            $response->setStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $response;
    }
}
