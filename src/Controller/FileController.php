<?php

namespace App\Controller;

use http\Exception;
use App\Entity\File;
use App\Repository\FileRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;

class FileController extends AbstractController
{
    /**
     * @Route("/download/{id}", name="app_download")
     * @param                $id
     * @param FileRepository $fileRepository
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function downloadAction($id, FileRepository $fileRepository) {
        if(!$this->isGranted("IS_AUTHENTICATED_FULLY"))
        {
            return $this->redirectToRoute("app_login");
        }
        try {
            $file = $fileRepository->findOneBy(['id'=>$id],[]);

            if (! $file) {
                $array = array (
                    'status' => 0,
                    'message' => 'File does not exist'
                );
                $response = new JsonResponse ( $array, 200 );
                return $response;
            }

            $displayName = $file->getName();
            $fileSubDirectory = $file->getUser()->getId();
            $file_with_path = "../var/uploads/".$fileSubDirectory."/".$displayName;
            $response = new BinaryFileResponse ( $file_with_path );
            $response->headers->set ( 'Content-Type', 'text/plain' );
            $response->setContentDisposition ( ResponseHeaderBag::DISPOSITION_ATTACHMENT, $displayName );
            return $response;
        } catch ( Exception $e ) {
            $array = array (
                'status' => 0,
                'message' => 'Download error'
            );
            $response = new JsonResponse ( $array, 400 );
            return $response;
        }
    }

    /**
     * @Route("/delete/{id}", name="app_delete")
     * @param                $id
     * @param FileRepository $fileRepository
     * @return Response
     */
    public function deleteAction($id, FileRepository $fileRepository): Response
    {
        if(!$this->isGranted("IS_AUTHENTICATED_FULLY"))
        {
            return $this->redirectToRoute("app_login");
        }
        try {
            $file = $fileRepository->findOneBy(['id'=>$id],[]);
            //var_dump($file);
            if (! $file) {
                $array = array (
                    'status' => 0,
                    'message' => 'File does not exist'
                );
                $response = new JsonResponse ( $array, 200 );
                return $response;
            }
            $displayName = $file->getName();
            $fileSubDirectory = $file->getUser()->getId();
            $file_with_path = "../var/uploads/".$fileSubDirectory."/".$displayName;

            $filesystem = new Filesystem();
            $filesystem->remove(['symlink', $file_with_path, 'activity.log']);

            $entityManager = $this->getDoctrine(File::class)->getManager();
            $entityManager->remove($file);
            $entityManager->flush();

        } catch ( Exception $e ) {
            $array = array (
                'status' => 0,
                'message' => 'Delete error'
            );
            $response = new JsonResponse ( $array, 500 );
            return $response;
        }
        $files = $fileRepository->findBy(['user' => $this->getUser()], ['upload_time' => 'DESC']);
        //var_dump($files);
        return $this->render('main/index.html.twig', [
            'files' => $files,
        ]);
    }
}
