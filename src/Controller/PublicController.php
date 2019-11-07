<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Partner;
use App\Entity\User;
use App\Service\NormalizerService as NS;
use App\Service\UtilityService as US;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse as JR;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class PublicController extends AbstractController
{
    /**
     * @Route("/debug", name="debug", methods="GET")
     */
    public function debug(Request $req, EntityManagerInterface $em) {
        $password = "password";
        return new JR(password_hash($password, PASSWORD_BCRYPT));
    }

    /**
     * @Route("/login", name="public-login", methods="POST")
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

        return new JR([
            'authKey' => $jwt,
            'user' => NS::getUser($user, true)
        ]);
    }

    /**
     * @Route("/public/members", name="public-members", methods="GET")
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
     */
    public function getPartners(EntityManagerInterface $em)
    {
        $partners = $em->getRepository(Partner::class)->findAll();
        $data = [];
        foreach ($partners as $p) array_push($data, NS::getPartner($p));
        return new JR($data);
    }

    /**
     * @Route("/public/events", name="public-events", methods={"GET"})
     */
    public function getEvents(EntityManagerInterface $em)
    {
        $events = $em->getRepository(Event::class)->findAll();
        $data = [];

        foreach ($events as $e) {
            $eventData = [
                'id' => $e->getId(),
                'title' => $e->getTitle(),
                'description' => $e->getDescription(),
                'start' => US::datetimeToString($e->getStart()),
                'end' => US::datetimeToString($e->getEnd()),
                'location' => $e->getLocation(),
                'privacy' => $e->getPrivacy(),
                'isConfirmed' => $e->getIsConfirmed(),
                'photos' => []
            ];

            foreach ($e->getPhotos() as $p) {
                array_push($eventData['photos'], NS::getPhoto($p));
            }
            array_push($data, $eventData);
        }

        return new JR($data);
    }
}
