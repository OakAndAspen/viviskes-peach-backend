<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Participation;
use App\Entity\User;
use App\Service\JsonService as JS;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse as JR;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use DateTime;
use Exception;

/**
 * @Route("/calendar")
 */
class EventController extends AbstractController implements TokenAuthenticatedController
{
    /**
     * @Route("", name="event-index", methods={"GET"})
     *
     * @param Request $req
     * @param EntityManagerInterface $em
     * @return JR
     */
    public function index(Request $req, EntityManagerInterface $em)
    {
        $events = $em->getRepository(Event::class)->findAll();
        $data = [];
        foreach ($events as $e) array_push($data, JS::getEvent($e, $req->get("user")));
        return new JR($data, Response::HTTP_OK);
    }

    /**
     * @Route("", name="event-create", methods={"POST"})
     *
     * @param Request $req
     * @param EntityManagerInterface $em
     * @return JR
     * @throws Exception
     */
    public function create(Request $req, EntityManagerInterface $em)
    {
        $p = new Event();
        $p->setTitle($req->get("title"));
        $p->setDescription($req->get("description"));
        $p->setStart(new DateTime($req->get("start")));
        $p->setEnd(new DateTime($req->get("end")));
        $p->setLocation($req->get("location"));
        $p->setPrivacy($req->get("privacy"));

        $em->persist($p);
        $em->flush();
        return new JR(JS::getEvent($p, $req->get("user")), Response::HTTP_CREATED);
    }

    /**
     * @Route("/{id}", name="event-show", methods={"GET"})
     *
     * @param Request $req
     * @param EntityManagerInterface $em
     * @param $id
     * @return JR
     */
    public function show(Request $req, EntityManagerInterface $em, $id)
    {
        $e = $em->getRepository(Event::class)->find($id);
        if(!$e) return new JR(null, Response::HTTP_NOT_FOUND);
        return new JR(JS::getEvent($e, $req->get("user"), true, true), Response::HTTP_OK);
    }

    /**
     * @Route("/{id}", name="event-update", methods={"PATCH"})
     *
     * @param Request $req
     * @param EntityManagerInterface $em
     * @param $id
     * @return JR
     * @throws Exception
     */
    public function update(Request $req, EntityManagerInterface $em, $id)
    {
        $e = $em->getRepository(Event::class)->find($id);
        if(!$e) return new JR(null, Response::HTTP_NOT_FOUND);

        if($req->get("title")) $e->setTitle($req->get("title"));
        if($req->get("description")) $e->setDescription($req->get("description"));
        if($req->get("start")) $e->setStart(new DateTime($req->get("start")));
        if($req->get("end")) $e->setEnd(new DateTime($req->get("end")));
        if($req->get("location")) $e->setLocation($req->get("location"));
        if($req->get("privacy")) $e->setPrivacy($req->get("privacy"));

        $em->persist($e);
        $em->flush();
        return new JR(JS::getEvent($e, $req->get("user")), Response::HTTP_OK);
    }

    /**
     * @Route("/{id}", name="event-delete", methods={"DELETE"})
     *
     * @param EntityManagerInterface $em
     * @param $id
     * @return JR
     */
    public function delete(EntityManagerInterface $em, $id)
    {
        $e = $em->getRepository(Event::class)->find($id);
        if(!$e) return new JR(null, Response::HTTP_NOT_FOUND);

        $em->remove($e);
        $em->flush();
        return new JR(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @Route("/participate", name="event-participate", methods={"POST"})
     *
     * @param Request $req
     * @param EntityManagerInterface $em
     * @return JR
     * @throws Exception
     */
    public function participate(Request $req, EntityManagerInterface $em)
    {
        $user = $req->get("user");
        $event = $em->getRepository(Event::class)->find($req->get("event"));
        $day = new DateTime($req->get("day"));
        $status = $req->get("status");
        if (!$event) return new JR(null, Response::HTTP_NOT_FOUND);
        if(!$day || !$status) return new JR(null, Response::HTTP_BAD_REQUEST);

        $part = $em->getRepository(Participation::class)->findOneBy([
            "user" => $user,
            "event" => $event,
            "day" => $day
        ]);

        if($part) $part->setStatus($status);
        else {
            $part = new Participation();
            $part->setEvent($event);
            $part->setUser($user);
            $part->setDay($day);
            $part->setStatus($status);
        }

        $em->persist($part);
        $em->flush();
        return new JR(null, Response::HTTP_NO_CONTENT);
    }
}
