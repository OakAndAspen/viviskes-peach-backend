<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\JsonResponse as JR;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/files")
 */
class FileController extends AbstractController implements TokenAuthenticatedController
{
    /**
     * @Route("", name="files-upload", methods={"POST"})
     *
     * @param Request $req
     * @param EntityManagerInterface $em
     * @return JR
     */
    public function upload(Request $req, EntityManagerInterface $em)
    {
        $name = /*$req->get("name") ||*/
            "coucou2.jpg";
        $file = $req->files->get("file");
        $url = "uploads\\" . $name;
        move_uploaded_file($file, $url);
        return new JsonResponse(["url" => "/uploads/" . $name . "?timestamp=" . mktime()]);
    }
}
