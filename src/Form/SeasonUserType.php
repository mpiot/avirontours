<?php

namespace App\Form;

use App\Entity\Season;
use App\Entity\SeasonUser;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SeasonUserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('season', EntityType::class, [
                'label' => 'Saison',
                'class' => Season::class,
                'query_builder' => function(EntityRepository $repository) {
                    return $repository->createQueryBuilder('season')
                        ->orderBy('season.name', 'DESC');
                },
                'choice_label' => 'name',
            ])
            ->add('medicalCertificate', MedicalCertificateType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => SeasonUser::class,
        ]);
    }
}
