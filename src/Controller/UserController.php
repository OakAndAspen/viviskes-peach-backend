<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\JsonService as JS;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse as JR;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/users")
 */
class UserController extends AbstractController implements TokenAuthenticatedController
{
    /**
     * @Route("", name="user-index", methods={"GET"})
     *
     * @param EntityManagerInterface $em
     * @return JR
     */
    public function index(EntityManagerInterface $em)
    {
        $users = $em->getRepository(User::class)->findAll();
        $data = [];
        foreach ($users as $p) array_push($data, JS::getUser($p));
        return new JR($data, Response::HTTP_OK);
    }

    /**
     * @Route("", name="user-create", methods={"POST"})
     *
     * @param Request $req
     * @param EntityManagerInterface $em
     * @return JR
     */
    public function create(Request $req, EntityManagerInterface $em)
    {
        $u = new User();
        $u->setFirstName($req->get("firstName"));
        $u->setLastName($req->get("lastName"));
        $u->setCelticName($req->get("celticName"));
        $u->setEmail($req->get("email"));
        $u->setPhone($req->get("phone"));
        $u->setPassword(password_hash($req->get("password"), PASSWORD_BCRYPT));
        $u->setAddress($req->get("address"));
        $u->setNpa($req->get("npa"));
        $u->setCity($req->get("city"));
        $u->setIsAdmin($req->get("isAdmin") || false);
        $u->setIsActive($req->get("isActive") || true);

        $em->persist($u);
        $em->flush();
        return new JR(JS::getUser($u), Response::HTTP_CREATED);
    }

    /**
     * @Route("/profile", name="user-show-profile", methods={"GET"})
     *
     * @param Request $req
     * @param EntityManagerInterface $em
     * @return JR
     */
    public function showProfile(Request $req, EntityManagerInterface $em)
    {
        $u = $req->get("user");
        if (!$u) return new JR(null, Response::HTTP_NOT_FOUND);
        return new JR(JS::getUser($u, true), Response::HTTP_OK);
    }

    /**
     * @Route("/{id}", name="user-show", methods={"GET"})
     *
     * @param EntityManagerInterface $em
     * @param $id
     * @return JR
     */
    public function show(EntityManagerInterface $em, $id)
    {
        $u = $em->getRepository(User::class)->find($id);
        if (!$u) return new JR(null, Response::HTTP_NOT_FOUND);
        return new JR(JS::getUser($u, true), Response::HTTP_OK);
    }

    /**
     * @Route("/profile", name="user-update-profile", methods={"PATCH"})
     *
     * @param Request $req
     * @param EntityManagerInterface $em
     * @return JR
     */
    public function updateProfile(Request $req, EntityManagerInterface $em)
    {
        $u = $req->get("user");

        if ($req->get("firstName")) $u->setFirstName($req->get("firstName"));
        if ($req->get("lastName")) $u->setLastName($req->get("lastName"));
        if ($req->get("celticName")) $u->setCelticName($req->get("celticName"));
        if ($req->get("email")) $u->setEmail($req->get("email"));
        if ($req->get("phone")) $u->setPhone($req->get("phone"));
        if ($req->get("password")) $u->setPassword(password_hash($req->get("password"), PASSWORD_BCRYPT));
        if ($req->get("address")) $u->setAddress($req->get("address"));
        if ($req->get("npa")) $u->setNpa($req->get("npa"));
        if ($req->get("city")) $u->setCity($req->get("city"));
        if ($req->get("isActive")) $u->setIsActive($req->get("isActive") || true);

        if ($req->get("mentor")) {
            $mentor = $em->getRepository(User::class)->find($req->get("mentor"));
            if (!$mentor) $u->setMentor(null);
            else {
                $u->setMentor($mentor);
                $mentor->setNewbie($u);
                $em->persist($mentor);
            }
        }

        if ($req->get("newbie")) {
            $newbie = $em->getRepository(User::class)->find($req->get("newbie"));
            if (!$newbie) $u->setNewbie(null);
            else {
                $u->setNewbie($newbie);
                $newbie->setMentor($u);
                $em->persist($newbie);
            }
        }

        $em->persist($u);
        $em->flush();
        return new JR(JS::getUser($u, true), Response::HTTP_OK);
    }

    /**
     * @Route("/image", name="user-update-image", methods={"POST"})
     *
     * @param Request $req
     * @return JR
     */
    public function updateImage(Request $req)
    {
        $user = $req->get("user");
        $file = $req->files->get("file");
        $url = "uploads\\users\\" . $user->getId() . ".jpg";
        move_uploaded_file($file, $url);
        return new JR(["url" => "/uploads/users/".$user->getId().".jpg?timestamp=" . mktime()]);
    }

    /**
     * @Route("/{id}", name="user-update", methods={"PATCH"})
     *
     * @param Request $req
     * @param EntityManagerInterface $em
     * @param $id
     * @return JR
     */
    public function update(Request $req, EntityManagerInterface $em, $id)
    {
        $u = $em->getRepository(User::class)->find($id);
        if (!$u) return new JR(null, Response::HTTP_NOT_FOUND);

        if ($req->get("firstName")) $u->setFirstName($req->get("firstName"));
        if ($req->get("lastName")) $u->setLastName($req->get("lastName"));
        if ($req->get("celticName")) $u->setCelticName($req->get("celticName"));
        if ($req->get("email")) $u->setEmail($req->get("email"));
        if ($req->get("phone")) $u->setPhone($req->get("phone"));
        if ($req->get("password")) $u->setPassword(password_hash($req->get("password"), PASSWORD_BCRYPT));
        if ($req->get("address")) $u->setAddress($req->get("address"));
        if ($req->get("npa")) $u->setNpa($req->get("npa"));
        if ($req->get("city")) $u->setCity($req->get("city"));
        if ($req->get("isAdmin")) $u->setIsAdmin($req->get("isAdmin") || false);
        if ($req->get("isActive")) $u->setIsActive($req->get("isActive") || true);

        if ($req->get("mentor")) {
            $mentor = $em->getRepository(User::class)->find($req->get("mentor"));
            if (!$mentor) $u->setMentor(null);
            else {
                $u->setMentor($mentor);
                $mentor->setNewbie($u);
                $em->persist($mentor);
            }
        }

        if ($req->get("newbie")) {
            $newbie = $em->getRepository(User::class)->find($req->get("newbie"));
            if (!$newbie) $u->setNewbie(null);
            else {
                $u->setNewbie($newbie);
                $newbie->setMentor($u);
                $em->persist($newbie);
            }
        }

        $em->persist($u);
        $em->flush();
        return new JR(JS::getUser($u, true), Response::HTTP_OK);
    }

    /**
     * @Route("/{id}", name="user-delete", methods={"DELETE"})
     *
     * @param EntityManagerInterface $em
     * @param $id
     * @return JR
     */
    public function delete(EntityManagerInterface $em, $id)
    {
        $u = $em->getRepository(User::class)->find($id);
        if (!$u) return new JR(null, Response::HTTP_NOT_FOUND);

        $em->remove($u);
        $em->flush();
        return new JR(null, Response::HTTP_NO_CONTENT);
    }
}
