<?php

namespace App\Controller;

use App\Entity\Event;
use App\Service\FormService;
use App\Service\NormalizerService as NS;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse as JR;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/event")
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

        $array = [];
        foreach ($events as $e) array_push($array, NS::getEvent($e, $req->get("authUser")));
        return new JR($array);
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
        $data = $req->get("event");
        if (!$data) return new JR("No data", Response::HTTP_BAD_REQUEST);

        $event = FormService::upsertEvent($em, $data);
        if (is_string($event)) return new JR($event, Response::HTTP_BAD_REQUEST);

        return new JR(NS::getEvent($event, $req->get("authUser")), Response::HTTP_CREATED);
    }

    /**
     * @Route("/{eventId}", name="event-show", methods={"GET"})
     *
     * @param Request $req
     * @param EntityManagerInterface $em
     * @param $eventId
     * @return JR
     */
    public function show(Request $req, EntityManagerInterface $em, $eventId)
    {
        $e = $em->getRepository(Event::class)->find($eventId);
        if(!$e) return new JR("Event not found", Response::HTTP_NOT_FOUND);
        return new JR(NS::getEvent($e, $req->get("authUser"), true, true));
    }

    /**
     * @Route("/{eventId}", name="event-update", methods={"PUT"})
     *
     * @param Request $req
     * @param EntityManagerInterface $em
     * @param $eventId
     * @return JR
     * @throws Exception
     */
    public function update(Request $req, EntityManagerInterface $em, $eventId)
    {
        $data = $req->get("event");
        if (!$data) return new JR("No data", Response::HTTP_BAD_REQUEST);

        $event = $em->getRepository(Event::class)->find($eventId);
        if(!$event) return new JR("Event not found", Response::HTTP_NOT_FOUND);

        $event = FormService::upsertEvent($em, $data, $event);
        if (is_string($event)) return new JR($event, Response::HTTP_BAD_REQUEST);

        return new JR(NS::getEvent($event, $req->get("authUser")));
    }

    /**
     * @Route("/{eventId}", name="event-delete", methods={"DELETE"})
     *
     * @param EntityManagerInterface $em
     * @param $eventId
     * @return JR
     */
    public function delete(EntityManagerInterface $em, $eventId)
    {
        $e = $em->getRepository(Event::class)->find($eventId);
        if(!$e) return new JR("Event not found", Response::HTTP_NOT_FOUND);

        $em->remove($e);
        $em->flush();
        return new JR("Event was deleted");
    }
}
