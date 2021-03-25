<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\FileUploader;
use Psr\Log\LoggerInterface;
use App\Entity\File;
use App\Repository\FileRepository;
use App\Repository\UserRepository;

class UploadController extends AbstractController
{
    /**
     * @Route("/upload_file", name="app_upload_file")
     */
        public function index(): Response
    {
        if(!$this->isGranted("IS_AUTHENTICATED_FULLY"))
        {
            return $this->redirectToRoute("app_login");
        }
        return $this->render('upload/index.html.twig', [
            'controller_name' => 'UploadController',
        ]);
    }

    /**
     * @Route("/file/upload", name="app_file_upload")
     * @param Request         $request
     * @param string          $uploadDir
     * @param FileUploader    $uploader
     * @param LoggerInterface $logger
     * @param FileRepository  $fr
     * @param UserRepository  $ur
     *
     * @return Response
     */
    public function upload(Request $request, string $uploadDir,
        FileUploader $uploader, LoggerInterface $logger,
        FileRepository $fr, UserRepository $ur): Response
    {
        if(!$this->isGranted("IS_AUTHENTICATED_FULLY"))
        {
            return $this->redirectToRoute("app_login");
        }

        $token = $request->get("token");

        if (!$this->isCsrfTokenValid('upload', $token))
        {
            $logger->info("CSRF failure");

            return new Response("Operation not allowed",  Response::HTTP_BAD_REQUEST,
                ['content-type' => 'text/plain']);
        }

        $file = $request->files->get('myfile');
        //var_dump($file);

        if (empty($file))
        {
            return new Response("No file specified",
                Response::HTTP_UNPROCESSABLE_ENTITY, ['content-type' => 'text/plain']);
        }

        $filename = $file->getClientOriginalName();

        //var_dump($fr->findOneBy(['name' => $filename],[]) );

        if( $fr->findOneBy(['name' => $filename],[]) === null) {


            $user = $this->getUser();

            $fr->findBy(['user' => $user], ['upload_time' => 'DESC']);

            $fileExt = $file->getClientMimeType();
            $fileSize = $file->getSize();
            $document = new File();
            $document->setName($filename);

            $document->setUser($user);
            $document->setExtension($fileExt);
            $document->setSize($fileSize);
            $document->setUploadTime(new \DateTime());

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($document);
            $entityManager->flush();


            $u = $ur->findOneBy(['email' => $user->getUsername()]);

            $uploadDir = $uploadDir.'/'.$u->getId().'/';
            $uploader->upload($uploadDir, $file, $filename);
        }
        $files = $fr->findBy(
            ['user' => $this->getUser()],
            ['upload_time' => 'DESC']
        );
            //var_dump($files);

        return $this->render('main/index.html.twig', [
            'files' => $files,
        ]);
    }


}
