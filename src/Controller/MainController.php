<?php

namespace App\Controller;

use App\Repository\CommentRepository;
use App\Repository\PostRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class MainController extends AbstractController
{
    #[Route('/', name: 'app_main')]
    public function index(): Response
    {
        if (!$this->isGranted("ROLE_USER")){
            return $this -> render('main/nouser.html.twig');
        }

        // Collect followers post 

        return $this->render('main/index.html.twig', []);
    }

    #[Route('/admin', name: 'app_admin')]
    public function admin(UserRepository $userRepository, PostRepository $postRepository, CommentRepository $commentRepository): Response
    {
        return $this->render('main/admin.html.twig', [
            'users' => $userRepository->findAll(),
            'reportedPosts' => $postRepository->findBy(['report' => true]),
            'comments' => $commentRepository->findAll()
        ]);
    }
}
