<?php

namespace App\Form;

use App\Entity\City;
use App\Entity\Place;
use App\Entity\Site;
use App\Entity\Sortie;
use App\Entity\User;
use App\Enum\State;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SortieFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom de la sortie'
            ])
            ->add('startDateTime', DateTimeType::class, [
                'label' => 'Date et heure de la sortie',
                'widget' => 'single_text'
            ])
            ->add('registrationDeadline', DateType::class, [
                'label' => 'Date limite d\'inscription',
                'widget' => 'single_text'
            ])
            ->add('maxRegistration', NumberType::class, [
                'label' => 'Nombre de places'
            ])
            ->add('duration', NumberType::class, [
                'label' => 'Durée',
                'attr' => [
                    'min' => 5,
                    'max' => 1440,
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description et infos',
                'required' => false
            ])
            ->add('city', EntityType::class, [
                'class' => City::class,
                'placeholder' => '-- Choisissez une ville --',
                'choice_label' => 'name',
                'label' => 'Ville',
                'mapped' => false,
                'choice_attr' => function(City $city) {
                    return [
                        'data-postal-code' => $city->getPostalCode(),
                    ];
                },
            ])
            ->add('place', EntityType::class, [
                'class' => Place::class,
                'placeholder' => '-- Choisissez d\'abord une ville --',
                'choice_label' => 'name',
                'label' => 'Lieu',
                'choice_attr' => function(Place $place) {
                    return [
                        'data-street' => $place->getStreet(),
                        'data-city' => $place->getCity()->getName(),
                        'data-cp' => $place->getCity()->getPostalCode(),
                        'data-latitude' => $place->getLatitude(),
                        'data-longitude' => $place->getLongitude(),
                    ];
                },
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Sortie::class,
        ]);
    }
}
