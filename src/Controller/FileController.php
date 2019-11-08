<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/file")
 */
class FileController extends AbstractController
{
    /**
     * @Route("", name="file-upload", methods={"POST"})
     */
    public function upload(Request $req, EntityManagerInterface $em)
    {
        $filename = uniqid() . ".jpg";
        $file = $req->files->get("upload");
        $url = "uploads" . DIRECTORY_SEPARATOR . "ckeditor" . DIRECTORY_SEPARATOR . $filename;
        move_uploaded_file($file, $url);
        return new JsonResponse([
            "uploaded" => true,
            "url" => $_ENV['SERVER_URL'] . "uploads/ckeditor/" . $filename
        ]);
    }
}
