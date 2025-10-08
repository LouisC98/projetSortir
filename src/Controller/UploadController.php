<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class UploadController extends AbstractController
{
    #[Route('/upload-photo', name: 'upload_photo', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function uploadPhoto(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        /** @var User $user */

        $photo = $request->files->get('photo');

        if ($photo) {
            $newFilename = uniqid().'.'.$photo->guessExtension();

            try {
                $photo->move(
                    $this->getParameter('kernel.project_dir').'/public/uploads/photos',
                    $newFilename
                );

                // Supprimer l’ancienne photo si existante
                if ($user->getPhotoFilename() && file_exists($this->getParameter('kernel.project_dir').'/public/uploads/photos/'.$user->getPhotoFilename())) {
                    unlink($this->getParameter('kernel.project_dir').'/public/uploads/photos/'.$user->getPhotoFilename());
                }

                // Mettre à jour l’utilisateur
                $user->setPhotoFilename($newFilename);
                $em->flush();

                $this->addFlash('success', 'Photo de profil mise à jour avec succès !');

            } catch (FileException $e) {
                $this->addFlash('error', 'Erreur lors de l’envoi du fichier.');
            }
        }

        return $this->redirectToRoute('profil'); // change 'profil' selon le nom de ta route
    }
}
