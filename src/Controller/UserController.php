<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\FormService;
use App\Service\NormalizerService as NS;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse as JR;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/user")
 */
class UserController extends AbstractController implements TokenAuthenticatedController
{
    /**
     * @Route("", name="user-index", methods={"GET"})
     */
    public function index(EntityManagerInterface $em)
    {
        $users = $em->getRepository(User::class)->findAll();
        $data = [];
        foreach ($users as $u) {
            if(!$u->getIsArchived()) {
                array_push($data, NS::getUser($u, true));
            }
        }
        return new JR($data);
    }

    /**
     * @Route("", name="user-create", methods={"POST"})
     */
    public function create(Request $req, EntityManagerInterface $em)
    {
        $data = $req->get("user");
        if (!$data) return new JR("No data", Response::HTTP_BAD_REQUEST);

        $user = FormService::upsertUser($em, $data);
        if (is_string($user)) return new JR($user, Response::HTTP_BAD_REQUEST);

        return new JR(NS::getUser($user), Response::HTTP_CREATED);
    }

    /**
     * @Route("/{userId}", name="user-show", methods={"GET"})
     */
    public function show(EntityManagerInterface $em, $userId)
    {
        $u = $em->getRepository(User::class)->find($userId);
        if (!$u) return new JR("User not found", Response::HTTP_NOT_FOUND);
        return new JR(NS::getUser($u, true));
    }

    /**
     * @Route("/image", name="user-update-image", methods={"POST"})
     */
    public function updateImage(Request $req)
    {
        $authUser = $req->get("authUser");
        $file = $req->files->get("file");
        $filename = $authUser->getId() . ".jpg";
        $url = "uploads" . DIRECTORY_SEPARATOR . "users" . DIRECTORY_SEPARATOR . $filename;
        move_uploaded_file($file, $url);
        return new JR(["url" => "/uploads/users/" . $authUser->getId() . ".jpg?timestamp=" . mktime()]);
    }

    /**
     * @Route("/{userId}", name="user-update", methods={"PUT"})
     */
    public function update(Request $req, EntityManagerInterface $em, $userId)
    {
        $data = $req->get("user");
        if (!$data) return new JR("No data", Response::HTTP_BAD_REQUEST);

        $user = $em->getRepository(User::class)->find($userId);
        if (!$user) return new JR("User not found", Response::HTTP_NOT_FOUND);

        $user = FormService::upsertUser($em, $data, $user);
        if (is_string($user)) return new JR($user, Response::HTTP_BAD_REQUEST);

        return new JR(NS::getUser($user, true));
    }

    /**
     * @Route("/{userId}", name="user-delete", methods={"DELETE"})
     */
    public function delete(EntityManagerInterface $em, $userId)
    {
        $u = $em->getRepository(User::class)->find($userId);
        if (!$u) return new JR("User not found", Response::HTTP_NOT_FOUND);

        $em->remove($u);
        $em->flush();
        return new JR("User was deleted", Response::HTTP_NO_CONTENT);
    }
}
