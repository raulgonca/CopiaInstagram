<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Form\CommentType;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/comment')]
final class CommentController extends AbstractController
{
    #[Route(name: 'app_comment_index', methods: ['GET'])]
    public function index(CommentRepository $commentRepository): Response
    {
        return $this->render('comment/index.html.twig', [
            'comments' => $commentRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_comment_new', methods: ['POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', 'You must be logged in to comment');
            return $this->redirectToRoute('app_login');
        }

        $postId = $request->request->get('post_id');
        $content = $request->request->get('content');

        if (empty($content)) {
            $this->addFlash('error', 'Comment cannot be empty');
            return $this->redirectToRoute('app_post_show', ['id' => $postId]);
        }

        try {
        $comment = new Comment();
            $comment->setOwner($user);
            $comment->setText($content);
            $comment->setDate(new \DateTime());
            $comment->setPost($entityManager->getReference('App\Entity\Post', $postId));

            $entityManager->persist($comment);
            $entityManager->flush();

            $this->addFlash('success', 'Comment added successfully');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Error saving comment');
        }

        return $this->redirectToRoute('app_post_show', ['id' => $postId]);
    }

    #[Route('/{id}', name: 'app_comment_show', methods: ['GET'])]
    public function show(Comment $comment): Response
    {
        return $this->render('comment/show.html.twig', [
            'comment' => $comment,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_comment_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Comment $comment, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_comment_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('comment/edit.html.twig', [
            'comment' => $comment,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_comment_delete', methods: ['POST'])]
    public function delete(Request $request, Comment $comment, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $comment->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($comment);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_admin', [], Response::HTTP_SEE_OTHER);
    }
}
