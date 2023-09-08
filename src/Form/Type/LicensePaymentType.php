<?php

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

namespace App\Form\Type;

use App\Entity\LicensePayment;
use App\Enum\PaymentMethod;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LicensePaymentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('method', EnumType::class, [
                'label' => 'Moyen de paiement',
                'class' => PaymentMethod::class,
                'choice_label' => 'label',
                'placeholder' => '--- Sélectionner un moyen de paiement ---',
            ])
            ->add('amount', MoneyType::class, [
                'label' => 'Montant',
                'divisor' => 100,
            ])
        ;

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $this->formModifier($event->getForm(), $event->getData()?->getMethod());
        });

        $builder->get('method')->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $this->formModifier($event->getForm()->getParent(), $event->getForm()->getData());
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => LicensePayment::class,
        ]);
    }

    public function formModifier(FormInterface $form, PaymentMethod $paymentMethod = null): void
    {
        $form
            ->add('checkNumber', TextType::class, [
                'label' => 'Numéro de chèque',
                'disabled' => true !== $paymentMethod?->hasCheckNumber(),
            ])
            ->add('checkDate', DateType::class, [
                'label' => 'Date du chèque',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'disabled' => true !== $paymentMethod?->hasCheckDate(),
            ])
        ;
    }
}
