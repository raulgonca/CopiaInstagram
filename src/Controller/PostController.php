<?php

namespace App\Controller;

use App\Entity\Like;
use App\Entity\Post;
use App\Form\PostType;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/post')]
final class PostController extends AbstractController
{
    #[Route(name: 'app_post_index', methods: ['GET'])]
    public function index(PostRepository $postRepository): Response
    {
        return $this->render('post/index.html.twig', [
            'posts' => $postRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_post_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $post = new Post();
        $post->setDate(new \DateTime());
        $post->setOwner($user);
        $post->setBanned(false);
        $post->setReport(false);

        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();

            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                // Sanitize filename
                $safeFilename = preg_replace('/[^a-zA-Z0-9]/', '_', $originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('posts_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash('error', 'There was an error uploading your image');
                    return $this->redirectToRoute('app_post_new');
                }

                $post->setImage($newFilename);
            }

            $entityManager->persist($post);
            $entityManager->flush();

            return $this->redirectToRoute('app_main');
        }

        return $this->render('post/new.html.twig', [
            'post' => $post,
            'form' => $form,
        ]);
    }


    // Funciones para aprobar o banear posts
    #[Route('/{id}/approve', name: 'app_post_approve', methods: ['POST'])]
    public function approvePost(Post $post, EntityManagerInterface $entityManager): Response
    {
        $post->setReport(false);
        $entityManager->flush();

        $this->addFlash('success', 'Post has been approved');
        return $this->redirectToRoute('app_admin');
    }

    #[Route('/{id}/ban', name: 'app_post_ban', methods: ['POST'])]
    public function banPost(Post $post, EntityManagerInterface $entityManager): Response
    {
        $post->setBanned(true);
        $post->setReport(false);
        $entityManager->flush();

        $this->addFlash('success', 'Post has been banned');
        return $this->redirectToRoute('app_admin');
    }


    #[Route('/{id}', name: 'app_post_show', methods: ['GET'])]
    public function show(Post $post, EntityManagerInterface $entityManager): Response
    {
        $likeCount = $entityManager->getRepository(Like::class)->count(['post' => $post]);
        $userLike = null;
        
        if ($this->getUser()) {
            $userLike = $entityManager->getRepository(Like::class)->findOneBy([
                'post' => $post,
                'owner' => $this->getUser()
            ]);
        }

        return $this->render('post/show.html.twig', [
            'post' => $post,
            'likeCount' => $likeCount,
            'hasLiked' => $userLike !== null
        ]);
    }

    #[Route('/{id}/edit', name: 'app_post_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Post $post, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_post_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('post/edit.html.twig', [
            'post' => $post,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_post_delete', methods: ['POST'])]
    public function delete(Request $request, Post $post, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $post->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($post);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_post_index', [], Response::HTTP_SEE_OTHER);
    }
    #[Route('/{id}/like', name: 'app_post_like', methods: ['POST'])]
    public function like(Post $post, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', 'Debes iniciar sesión para dar like');
            return $this->redirectToRoute('app_login');
        }

        $likeRepository = $entityManager->getRepository(Like::class);
        $existingLike = $likeRepository->findOneBy([
            'post' => $post,
            'owner' => $user
        ]);

        if ($existingLike) {
            $entityManager->remove($existingLike);
            $this->addFlash('success', 'Has quitado tu like');
        } else {
            $like = new Like();
            $like->setPost($post);
            $like->setOwner($user);
            $entityManager->persist($like);
            $this->addFlash('success', 'Like añadido!');
        }

        $entityManager->flush();
        return $this->redirectToRoute('app_post_show', ['id' => $post->getId()]);
    }
}
