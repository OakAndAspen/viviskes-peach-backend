<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\UtilityService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class PublicController extends AbstractController
{
    /**
     * @Route("/login", methods="POST")
     *
     * @param Request $req
     * @param EntityManagerInterface $em
     * @return JsonResponse
     */
    public function login(Request $req, EntityManagerInterface $em)
    {
        if (!$req->get('email') || !$req->get('password')) {
            return new JsonResponse(['loginMissingData']);
        }

        $user = $em->getRepository(User::class)->findOneBy([
            'email' => $req->get('email')
        ]);

        if (!$user) return new JsonResponse(['userNotFound']);

        if (!password_verify($req->get('password'), $user->getPassword())) {
            return new JsonResponse(['loginPwIncorrect']);
        }

        // Create a JWT
        $jwt = UtilityService::generateJWT($user);
        $em->flush();

        return new JsonResponse(['authKey' => $jwt]);
    }

    /**
     * @Route("/public/members", methods="GET")
     *
     * @param Request $req
     * @param EntityManagerInterface $em
     * @return JsonResponse
     */
    public function getMembers(Request $req, EntityManagerInterface $em) {
        $data = [];
        foreach($em->getRepository(User::class)->findAll() as $u) {
            if($u->getCelticName()) {
                array_push($data, [
                    "id" => $u->getId(),
                    "celticName" => $u->getCelticName(),
                    "hasPhoto" => file_exists("uploads\\users\\".$u->getId().".jpg")
                ]);
            }
        }
        return new JsonResponse($data);
    }
}
