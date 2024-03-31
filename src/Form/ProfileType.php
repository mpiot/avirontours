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

namespace App\Form;

use App\Entity\PostalCode;
use App\Entity\User;
use App\Form\Type\LaneTypeType;
use App\Repository\PostalCodeRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProfileType extends AbstractType
{
    public function __construct(private readonly PostalCodeRepository $repository)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Email',
            ])
            ->add('phoneNumber', TelType::class, [
                'label' => 'Numéro de téléphone',
                'required' => false,
            ])
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
            ])
            ->add('laneNumber', TextType::class, [
                'label' => 'Numéro',
            ])
            ->add('laneType', LaneTypeType::class, [
                'label' => 'Type de voie',
            ])
            ->add('laneName', TextType::class, [
                'label' => 'Nom de voie',
            ])
            ->add('postalCode', TextType::class, [
                'label' => 'Code postal',
                'attr' => ['autocomplete' => 'postal-code'],
            ])
            ->add('firstLegalGuardian', LegalGuardianType::class, [
                'label' => false,
                'required' => false,
            ])
            ->add('secondLegalGuardian', LegalGuardianType::class, [
                'label' => false,
                'required' => false,
            ])
            ->add('clubEmailAllowed', CheckboxType::class, [
                'label' => 'Recevoir les emails du club',
                'label_attr' => ['class' => 'checkbox-custom'],
                'required' => false,
            ])
        ;

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $this->formModifier($event->getForm(), $event->getData()->getPostalCode());
        });

        $builder->get('postalCode')->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $this->formModifier($event->getForm()->getParent(), $event->getForm()->getData());
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }

    public function formModifier(FormInterface $form, ?string $postalCode = null): void
    {
        $cities = null === $postalCode ? [] : $this->repository->findBy(['postalCode' => $postalCode]);
        $cities = array_map(fn (PostalCode $postalCode) => $postalCode->getCity(), $cities);

        $form->add('city', ChoiceType::class, [
            'placeholder' => '',
            'choices' => array_combine($cities, $cities),
        ]);
    }
}
