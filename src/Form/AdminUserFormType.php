<?php

namespace App\Form;

use App\Entity\Site;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class AdminUserFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = $options['data']->getId() !== null;

        $builder
            ->add('pseudo', TextType::class, [
                'label' => 'Pseudo',
                'attr' => ['class' => 'w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 focus:ring-2 transition-all duration-200 py-3 px-4 text-base', 'placeholder' => 'Ex: john_doe'],
                'constraints' => [
                    new NotBlank(message: "Le pseudo est obligatoire"),
                    new Length(min: 3, max: 50, minMessage: "Le pseudo doit contenir au moins {{ limit }} caractères"),
                ],
            ])
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'attr' => ['class' => 'w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 focus:ring-2 transition-all duration-200 py-3 px-4 text-base', 'placeholder' => 'Ex: John'],
                'constraints' => [
                    new NotBlank(message: "Le prénom est obligatoire"),
                    new Length(max: 50),
                ],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
                'attr' => ['class' => 'w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 focus:ring-2 transition-all duration-200 py-3 px-4 text-base', 'placeholder' => 'Ex: Doe'],
                'constraints' => [
                    new NotBlank(message: "Le nom est obligatoire"),
                    new Length(max: 50),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Adresse e-mail',
                'attr' => ['class' => 'w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 focus:ring-2 transition-all duration-200 py-3 px-4 text-base', 'placeholder' => 'exemple@email.com'],
                'constraints' => [
                    new NotBlank(message: "L'email est obligatoire"),
                    new Email(message: "L'email n'est pas valide"),
                    new Length(max: 180),
                ],
            ])
            ->add('phone', TextType::class, [
                'label' => 'Téléphone',
                'attr' => [
                    'class' => 'w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 focus:ring-2 transition-all duration-200 py-3 px-4 text-base',
                    'placeholder' => '0123456789'
                ],
                'constraints' => [
                    new NotBlank(message: "Le téléphone est obligatoire"),
                    new Length(max: 10, exactMessage: "Le téléphone doit contenir {{ limit }} chiffres"),
                ],
            ])
            ->add('site', EntityType::class, [
                'class' => Site::class,
                'choice_label' => 'name',
                'label' => 'Site de rattachement',
                'placeholder' => 'Choisissez un site',
                'attr' => ['class' => 'w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 focus:ring-2 transition-all duration-200 py-3 px-4 text-base'],
                'constraints' => [
                    new NotBlank(message: "Le site est obligatoire"),
                ],
            ])
            ->add('plainPassword', PasswordType::class, [
                'mapped' => false,
                'label' => 'Mot de passe',
                'attr' => ['class' => 'w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 focus:ring-2 transition-all duration-200 py-3 px-4 text-base', 'placeholder' => $isEdit ? 'Laisser vide pour ne pas changer' : '••••••••'],
                'constraints' => $isEdit ? [] : [
                    new NotBlank(message: "Le mot de passe est obligatoire"),
                    new Length(min: 6, minMessage: "Le mot de passe doit contenir au moins {{ limit }} caractères"),
                ],
                'required' => !$isEdit,
                'help' => $isEdit ? 'Laisser vide pour conserver le mot de passe actuel' : 'Minimum 6 caractères',
            ])
            ->add('active', CheckboxType::class, [
                'label' => 'Compte actif',
                'required' => false,
                'attr' => ['class' => 'rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50'],
                'help' => 'Un compte inactif ne peut pas se connecter',
            ])
            ->add('roles', ChoiceType::class, [
                'label' => 'Rôles',
                'choices' => [
                    'Utilisateur' => 'ROLE_USER',
                    'Administrateur' => 'ROLE_ADMIN',
                ],
                'multiple' => true,
                'expanded' => true,
                'attr' => ['class' => 'mt-2'],
                'help' => 'ROLE_USER est ajouté automatiquement',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
