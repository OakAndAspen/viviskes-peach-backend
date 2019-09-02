<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Partner;
use App\Entity\User;
use App\Service\JsonService as JS;
use App\Service\UtilityService as US;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse as JR;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PublicController extends AbstractController
{
    /**
     * @Route("/login", name="public-login", methods="POST")
     *
     * @param Request $req
     * @param EntityManagerInterface $em
     * @return JR
     */
    public function login(Request $req, EntityManagerInterface $em)
    {
        if (!$req->get('email') || !$req->get('password')) {
            return new JR(['loginMissingData']);
        }

        $user = $em->getRepository(User::class)->findOneBy([
            'email' => $req->get('email')
        ]);

        if (!$user) return new JR(['userNotFound']);

        if (!password_verify($req->get('password'), $user->getPassword())) {
            return new JR(['loginPwIncorrect'], 400);
        }

        // Create a JWT
        $jwt = US::generateJWT($user);
        $em->flush();

        return new JR(['authKey' => $jwt]);
    }

    /**
     * @Route("/public/members", name="public-members", methods="GET")
     *
     * @param Request $req
     * @param EntityManagerInterface $em
     * @return JR
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
        return new JR($data);
    }

    /**
     * @Route("/public/partners", name="public-partners", methods={"GET"})
     *
     * @param EntityManagerInterface $em
     * @return JR
     */
    public function getPartners(EntityManagerInterface $em)
    {
        $partners = $em->getRepository(Partner::class)->findAll();
        $data = [];
        foreach ($partners as $p) array_push($data, JS::getPartner($p));
        return new JR($data, Response::HTTP_OK);
    }

    /**
     * @Route("/public/events", name="public-events", methods={"GET"})
     *
     * @param EntityManagerInterface $em
     * @return JR
     */
    public function getEvents(EntityManagerInterface $em)
    {
        $events = $em->getRepository(Event::class)->findAll();
        $data = [];

        foreach ($events as $e) array_push($data, [
            'id' => $e->getId(),
            'title' => $e->getTitle(),
            'description' => $e->getDescription(),
            'start' => US::datetimeToString($e->getStart()),
            'end' => US::datetimeToString($e->getEnd()),
            'location' => $e->getLocation(),
            'privacy' => $e->getPrivacy()
        ]);

        return new JR($data, Response::HTTP_OK);
    }
}
