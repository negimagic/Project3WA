<?php

namespace App\Controller;

use App\Entity\Project;
use App\Form\ProjectType;
use App\Form\UpdateProjectType;
use App\Repository\UserRepository;
use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ProjectController extends AbstractController
{
    /**
     * @Route("/project", name="project")
     */
    public function index(ProjectRepository $projectRepository, PaginatorInterface $paginator, Request $request): Response
    {           
        $data = $projectRepository->findAll();

        $projects = $paginator->paginate(
            $data, // Requête contenant les données à paginer (ici nos projets)
            $request->query->getInt('page', 1), // Numéro de la page en cours, passé dans l'URL, 1 si aucune page
            2 // Nombre de résultats par page
        );
        return $this->render('project/index.html.twig', [
            'controller_name' => 'ProjectController',
            'projects' => $projects
        ]);
    }

    /**
     * @Route("/project/view/{id}", name="projectById")
     */
    public function projectById(Project $project): Response
    {   
        
        return $this->render('project/index.html.twig', [
            'controller_name' => 'ProjectController',
            'project' => $project,
        ]);
    }

    /**
     * @Route("/project/add", name="add_project")
     * @IsGranted("ROLE_USER")
     */
    public function createProject(Request $request, SluggerInterface $slugger): Response
    {
        $project = new Project();
        $form = $this->createForm(ProjectType::class, $project);
        $form->handleRequest($request);
        $user = $this->getUser();

        if ($form->isSubmitted() && $form->isValid()) {
            
            $project->setUser($user);

            /** @var UploadedFile $brochureFile */
            $brochureFile = $form->get('image')->getData();

            // this condition is needed because the 'brochure' field is not required
            // so the PDF file must be processed only when a file is uploaded
            if ($brochureFile) {
                $originalFilename = pathinfo($brochureFile->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$brochureFile->guessExtension();

                // Move the file to the directory where brochures are stored
                try {
                    $brochureFile->move(
                        $this->getParameter('image_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    // ... handle exception if something happens during file upload
                }

                // updates the 'brochureFilename' property to store the PDF file name
                // instead of its contents
                $project->setImage($newFilename);
            }


        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($project);
        $entityManager->flush();


            return $this->redirectToRoute('user');
        }


        return $this->render('project/add.html.twig', [
            'controller_name' => 'ProjectController',
            'ProjectForm' => $form->createView()
        ]);
    }

    /**
     * @Route("/project/delete/{id}", name="del_project")
     * @IsGranted("ROLE_USER")
     */
    public function deleteProject(Project $project): Response
    {

        $manager = $this->getDoctrine()->getManager();
        $manager->remove($project);
        $manager->flush();

        return $this->redirectToRoute('user');
    }

    /**
     * @Route("/project/update/{id}", name="upd_project")
     * @IsGranted("ROLE_USER")
     */
    public function updateProject(Request $request, Project $project, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(UpdateProjectType::class, $project);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $project->setTitle(
                $form->get('title')->getData()
            );
            $project->setDescription(
                $form->get('description')->getData()
            );

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($project);
            $entityManager->flush();


            return $this->redirectToRoute('user');
        }

        return $this->render('project/upd.html.twig', [
            'controller_name' => 'ProjectController',
            'project' => $project,
            'UpdateForm' => $form->createView()
        ]);
    }

    public function fectchUsers(UserRepository $userRepository){
        $users = $userRepository->findAll();
    }

    /**
     * @Route("/project/{id}/addUser", name="add_users")
     * @IsGranted("ROLE_USER")
     */
    public function addUser(Project $project) {
        return $this->render('project/addMember.html.twig', [
            'controller_name' => 'ProjectController',
            'project' => $project,
        ]);
    }

    /**
     * @Route("/users/{search}", name="search_users", methods={"GET"})
     * @IsGranted("ROLE_USER")
     */
    public function userList(UserRepository $userRepository, $search = null, Request $request): Response
    {
        if ($request->get('xhr')) {
            $users = [];

            if (!empty($search) && strlen($search) > 2) {
                $users = $userRepository->findByNameEmail($search);
            }

            return $this->render('project/_searchMember.html.twig', [
                'users' => $users
            ]);
        }

        return $this->redirectToRoute('user');
    }

    /**
     * @Route("/{id}/addUser/save", name="add_users_save", methods={"POST"})
     * @IsGranted("ROLE_USER")
     */
    public function addUserSave(Project $project, Request $request, UserRepository $userRepository, EntityManagerInterface $entityManagerInterface): Response
    {
        $members = $request->get('members');
        if(!empty($members)) {
            foreach($members as $memberId){
                $member = $userRepository->find($memberId);
                $project->addUserId($member);
                $entityManagerInterface->persist($project);
            }

            $entityManagerInterface->flush();
        }
        return $this->redirectToRoute('user');
    }


}
