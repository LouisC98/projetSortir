<?php

namespace App\Form;

use App\Entity\Site;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SortieFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('site', EntityType::class, [
                'class' => Site::class,
                'choice_label' => 'name',
                'placeholder' => 'Tous les sites',
                'required' => false,
                'attr' => [
                    'class' => 'form-select rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500'
                ]
            ])
            ->add('name', TextType::class, [
                'label' => 'Nom de la sortie',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Rechercher par nom...',
                    'class' => 'form-input rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500'
                ]
            ])
            ->add('dateDebut', DateType::class, [
                'label' => 'Date de début',
                'widget' => 'single_text',
                'required' => false,
                'attr' => [
                    'class' => 'form-input rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500'
                ]
            ])
            ->add('dateFin', DateType::class, [
                'label' => 'Date de fin',
                'widget' => 'single_text',
                'required' => false,
                'attr' => [
                    'class' => 'form-input rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500'
                ]
            ])
            ->add('mesSorties', CheckboxType::class, [
                'label' => 'Mes sorties organisées',
                'required' => false,
                'attr' => [
                    'class' => 'form-checkbox rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50'
                ]
            ])
            ->add('sortiesInscrit', CheckboxType::class, [
                'label' => 'Sorties auxquelles je suis inscrit(e)',
                'required' => false,
                'attr' => [
                    'class' => 'form-checkbox rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50'
                ]
            ])
            ->add('sortiesNonInscrit', CheckboxType::class, [
                'label' => 'Sorties auxquelles je ne suis pas inscrit(e)',
                'required' => false,
                'attr' => [
                    'class' => 'form-checkbox rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50'
                ]
            ])
            ->add('sortiesPassees', CheckboxType::class, [
                'label' => 'Sorties passées',
                'required' => false,
                'attr' => [
                    'class' => 'form-checkbox rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50'
                ]
            ])
            ->add('rechercher', SubmitType::class, [
                'label' => 'Rechercher',
                'attr' => [
                    'class' => 'w-full bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white font-medium py-2 px-4 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'method' => 'GET',
            'csrf_protection' => false,
        ]);
    }

    public function getBlockPrefix(): string
    {
        // Retourner une chaîne vide pour que les champs ne soient pas préfixés (params GET plats)
        return '';
    }
}
