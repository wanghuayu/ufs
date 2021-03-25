<?php

namespace App\Controller;

use App\Repository\FileRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends AbstractController
{
    /**
     * @Route("/", name="main")
     * @param Request        $request
     * @param FileRepository $fileRepository
     *
     * @return Response
     */
    public function index(Request $request, FileRepository $fileRepository): Response
    {
        /**
         * If not login, redirect user to login page
         */
        if(!$this->isGranted("IS_AUTHENTICATED_FULLY"))
        {
            return $this->redirectToRoute("app_login");
        }

        $files = $fileRepository->findBy(['user' => $this->getUser()], ['upload_time' => 'DESC']);

        return $this->render('main/index.html.twig', [
            'files' => $files,
        ]);
    }
}
