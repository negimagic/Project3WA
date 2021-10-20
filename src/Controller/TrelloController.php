<?php

namespace App\Controller;

use App\Repository\ProjectRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class TrelloController extends AbstractController
{
    /**
     * @Route("/", name="home")
     */
    public function index(ProjectRepository $projectRepository, PaginatorInterface $paginator, Request $request): Response

    {
        $data = $projectRepository->findAll();

        $projects = $paginator->paginate(
            $data, // Requête contenant les données à paginer (ici nos projets)
            $request->query->getInt('page', 1), // Numéro de la page en cours, passé dans l'URL, 1 si aucune page
            3 // Nombre de résultats par page
        );

        return $this->render('trello/index.html.twig', [
            'controller_name' => 'TrelloController',
            'projects' => $projects
        ]);
    }
}
