<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/user')]
final class UserController extends AbstractController
{
    #[Route(name: 'app_user_index', methods: ['GET'])]
    public function index(int $id, UserRepository $userRepository): Response
    {
        return $this->render('user/index.html.twig', [
            'user' => $userRepository->findById($id),
        ]);
    }

    #[Route('/new', name: 'app_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    // Funcion para administrar usuarios (banear)
    #[Route('/{id}/toggle-ban', name: 'app_user_toggle_ban', methods: ['POST'])]
    public function toggleBan(User $user, EntityManagerInterface $entityManager): Response
    {
        // Toggle the banned status
        $user->setBanned(!$user->isBanned());
        $entityManager->flush();

        $this->addFlash(
            'success',
            sprintf(
                'User %s has been %s',
                $user->getUsername(),
                $user->isBanned() ? 'banned' : 'unbanned'
            )
        );

        return $this->redirectToRoute('app_admin');
    }



    #[Route('/{id}', name: 'app_user_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        return $this->render('user/index.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($user);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/unfollow', name: 'app_user_unfollow', methods: ['GET'])]
    public function unfollowUser(int $id, UserRepository $userRepository, EntityManager $entityManager): Response
    {
        $user = $userRepository->findById($this->getUser());
        if ($user->getFollows()->contains($id)) {
            $user->getFollows()->removeElement($id);
            $entityManager->persist($user);
            $entityManager->flush();
        }


        return $this->render('user/index.html.twig', [
            'user' => $userRepository->findById($id),
        ]);
    }

    #[Route('/{id}/follow', name: 'app_user_follow', methods: ['GET'])]
    public function followUser(int $id, UserRepository $userRepository, EntityManager $entityManager): Response
    {
        $user = $userRepository->findById($this->getUser());
        if ($user->getFollows()->contains($id)===false) {
            $user->getFollows()->addElement($id);
            $entityManager->persist($user);
            $entityManager->flush();
        }

        return $this->render('user/index.html.twig', [
            'user' => $userRepository->findById($id),
        ]);
    }

}
