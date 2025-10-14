<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Form\CommentType;
use App\Service\CommentService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/comment', name: 'app_comment')]
final class CommentController extends AbstractController
{
    public function __construct(private readonly CommentService $commentService)
    {
    }

    #[Route('/{id}/delete', name: '_delete', methods: ['POST'])]
    public function delete(Comment $comment, Request $request): Response
    {
        $this->denyAccessUnlessGranted('COMMENT_DELETE', $comment);

        if ($this->isCsrfTokenValid('delete_'.$comment->getId(), $request->request->get('_token'))) {
            $this->commentService->delete($comment);
            $this->addFlash("success", "Commentaire supprimé");
        }
        $redirect = $request->request->get('redirect');
        if ($redirect) {
            return $this->redirect($redirect);
        }
        return $this->redirectToRoute('app_sortie_show', ['id' => $comment->getSortie()->getId()]);
    }

    #[Route('/{id}/edit', name: '_edit', methods: ['POST'])]
    public function edit(Request $request, Comment $comment, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('COMMENT_EDIT', $comment);

        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            if ($request->isXmlHttpRequest()) {
                return $this->json(['content' => $comment->getContent()]);
            }

            $this->addFlash('success', 'Commentaire modifié avec succès.');
            return $this->redirectToRoute('app_sortie_show', ['id' => $comment->getSortie()->getId()]);
        }

        if ($request->isXmlHttpRequest()) {
            return $this->render('comment/_edit_form.html.twig', [
                'comment' => $comment,
                'editForm' => $form->createView(),
            ], new Response(null, 422));
        }

        foreach ($form->getErrors(true) as $error) {
            $this->addFlash('error', $error->getMessage());
        }

        return $this->redirectToRoute('app_sortie_show', ['id' => $comment->getSortie()->getId()]);
    }

    #[Route('/{id}/edit-form', name: '_edit_form', methods: ['GET'])]
    public function getEditForm(Comment $comment): Response
    {
        $this->denyAccessUnlessGranted('COMMENT_EDIT', $comment);

        $form = $this->createForm(CommentType::class, $comment, [
            'action' => $this->generateUrl('app_comment_edit', ['id' => $comment->getId()]),
            'method' => 'POST',
        ]);

        return $this->render('comment/_edit_form.html.twig', [
            'comment' => $comment,
            'editForm' => $form->createView(),
        ]);
    }
}
