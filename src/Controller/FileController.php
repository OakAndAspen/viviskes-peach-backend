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
class FileController extends AbstractController implements TokenAuthenticatedController
{
    /**
     * @Route("/forum", name="forum-upload", methods={"POST"})
     */
    public function forumUpload(Request $req, EntityManagerInterface $em)
    {
        $filename = uniqid() . ".jpg";
        $file = $req->files->get("file");
        $url = "uploads" . DIRECTORY_SEPARATOR . "forum" . DIRECTORY_SEPARATOR . $filename;
        move_uploaded_file($file, $url);
        return new JsonResponse(["url" => $_ENV['SERVER_URL']."uploads/forum/" . $filename]);
    }
}
