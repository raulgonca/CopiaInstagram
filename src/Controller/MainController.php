<?php

namespace App\Controller;

use App\Repository\PostRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class MainController extends AbstractController
{
    #[Route('/main', name: 'app_main')]
    public function index(): Response
    {
        return $this->render('main/index.html.twig', [
            'controller_name' => 'MainController',
        ]);
    }

    #[Route('/admin', name: 'app_admin')]
    public function admin(UserRepository $userRepository, PostRepository $postRepository): Response
    {
        $users = $userRepository->findAll();
        $reportedPosts = $postRepository->findBy(['report' => true]);

        return $this->render('main/admin.html.twig', [
            'users' => $users,
            'reportedPosts' => $reportedPosts
        ]);
    }
}
