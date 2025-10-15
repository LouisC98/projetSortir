<?php
// `src/Controller/ParticipantGroupController.php`
namespace App\Controller;

use App\Entity\ParticipantGroup;
use App\Entity\ParticipantGroupMember;
use App\Entity\User;
use App\Form\ParticipantGroupType;
use App\Security\Voter\ParticipantGroupVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/mes-groupes')]
class ParticipantGroupController extends AbstractController
{
    #[Route('', name: 'app_groups_index', methods: ['GET'])]
    public function index(EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var User $user */
        $user = $this->getUser();

        $groups = $em->getRepository(ParticipantGroup::class)
            ->findAllAccessibleByUser($user);

        return $this->render('groups/index.html.twig', [
            'groups' => $groups,
        ]);
    }

    #[Route('/nouveau', name: 'app_groups_new', methods: ['GET','POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var User $user */
        $user = $this->getUser();

        $group = new ParticipantGroup();
        $group->setOwner($user);

        $form = $this->createForm(ParticipantGroupType::class, $group, ['owner' => $user]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Persister le groupe d'abord pour obtenir un ID
            $em->persist($group);
            $em->flush();

            // Ensuite ajouter les membres
            $selectedUsers = $form->get('members')->getData();
            foreach ($selectedUsers as $selectedUser) {
                $member = new ParticipantGroupMember();
                $member->setUser($selectedUser);
                $group->addMember($member);
            }

            $em->flush();

            $this->addFlash('success', 'Groupe privé créé.');
            return $this->redirectToRoute('app_groups_index');
        }

        // Récupérer tous les utilisateurs actifs pour la sélection
        $allUsers = $em->getRepository(User::class)->findBy(['active' => true], ['pseudo' => 'ASC']);

        return $this->render('groups/form.html.twig', [
            'form' => $form,
            'group' => $group,
            'allUsers' => $allUsers,
        ]);
    }

    #[Route('/{id}/modifier', name: 'app_groups_edit', methods: ['GET','POST'])]
    public function edit(ParticipantGroup $group, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted(ParticipantGroupVoter::EDIT, $group);

        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(ParticipantGroupType::class, $group, ['owner' => $user]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Re-synchronisation des membres.
            $selectedUsers = $form->get('members')->getData();

            // Index existants
            $existing = [];
            foreach ($group->getMembers() as $gm) {
                $existing[$gm->getUser()->getId()] = $gm;
            }

            // Ajouts
            foreach ($selectedUsers as $user) {
                if (!isset($existing[$user->getId()])) {
                    $gm = new ParticipantGroupMember();
                    $gm->setUser($user);
                    $group->addMember($gm);
                } else {
                    unset($existing[$user->getId()]);
                }
            }

            // Suppressions restantes
            foreach ($existing as $gm) {
                $group->removeMember($gm);
                $em->remove($gm);
            }

            $em->flush();

            $this->addFlash('success', 'Groupe privé mis à jour.');
            return $this->redirectToRoute('app_groups_index');
        }

        // Pré-remplir le champ non mappé
        $form->get('members')->setData(
            array_map(fn($gm) => $gm->getUser(), $group->getMembers()->toArray())
        );

        // Récupérer tous les utilisateurs actifs pour la sélection
        $allUsers = $em->getRepository(User::class)->findBy(['active' => true], ['pseudo' => 'ASC']);

        return $this->render('groups/form.html.twig', [
            'form' => $form,
            'group' => $group,
            'allUsers' => $allUsers,
        ]);
    }

    #[Route('/{id}', name: 'app_groups_delete', methods: ['POST'])]
    public function delete(ParticipantGroup $group, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted(ParticipantGroupVoter::DELETE, $group);

        if ($this->isCsrfTokenValid('delete_group_'.$group->getId(), $request->request->get('_token'))) {
            $em->remove($group);
            $em->flush();
            $this->addFlash('success', 'Groupe supprimé.');
        }

        return $this->redirectToRoute('app_groups_index');
    }
}
