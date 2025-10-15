<?php
// `src/Form/ParticipantGroupType.php`
namespace App\Form;

use App\Entity\ParticipantGroup;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ParticipantGroupType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $owner = $options['owner'];

        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom du groupe',
                'required' => true,
            ])
            ->add('members', EntityType::class, [
                'class' => User::class,
                'label' => 'Participants',
                'multiple' => true,
                'expanded' => true, // Checkboxes pour un meilleur affichage
                'required' => false,
                'choice_label' => 'pseudo',
                'query_builder' => function (EntityRepository $er) use ($owner) {
                    return $er->createQueryBuilder('u')
                        ->andWhere('u != :owner')
                        ->andWhere('u.active = true')
                        ->setParameter('owner', $owner)
                        ->orderBy('u.pseudo', 'ASC');
                },
                'mapped' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('owner');
        $resolver->setDefaults([
            'data_class' => ParticipantGroup::class,
            'csrf_protection' => true,
        ]);
    }
}
