<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Post;
use App\Repository\UserRepository;
use App\Repository\PostRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SearchController extends AbstractController
{
    #[Route('/search', name: 'app_search')]
    public function search(Request $request, UserRepository $userRepository, PostRepository $postRepository): Response
    {
        $query = $request->query->get('q', '');
        $results = [
            'users' => [],
            'posts' => []
        ];
    
        if ($query) {
            $results['users'] = $userRepository->searchUsers($query);
            $results['posts'] = $postRepository->searchPosts($query);
            
            // Para depuración
            dump($results);
        }
    
        return $this->render('search/search.html.twig', [
            'query' => $query,
            'results' => $results,
        ]);
    }
}