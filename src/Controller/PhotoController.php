<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Photo;
use App\Service\FormService;
use App\Service\NormalizerService as NS;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse as JR;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/photo")
 */
class PhotoController extends AbstractController implements TokenAuthenticatedController
{
    /**
     * @Route("", name="photo-index", methods={"GET"})
     */
    public function index(Request $req, EntityManagerInterface $em)
    {
        $eventId = $req->get("event");
        if (!$eventId) return new JR("Missing data", Response::HTTP_BAD_REQUEST);
        $event = $em->getRepository(Event::class)->find($eventId);
        if (!$event) return new JR("Event not found", Response::HTTP_BAD_REQUEST);

        $array = [];
        foreach ($event->getPhotos() as $p) array_push($array, NS::getPhoto($p));
        return new JR($array);
    }

    /**
     * @Route("", name="photo-create", methods={"POST"})
     */
    public function create(Request $req, EntityManagerInterface $em)
    {
        $event = $req->get("event");
        $file = $req->files->get("file");

        if (!$event || !$file) return new JR("No data", Response::HTTP_BAD_REQUEST);

        $photo = FormService::upsertPhoto($em, ["event" => $event]);
        if (is_string($photo)) return new JR($photo, Response::HTTP_BAD_REQUEST);

        $url = "uploads" . DIRECTORY_SEPARATOR . "gallery" . DIRECTORY_SEPARATOR . $photo->getId() . ".jpg";
        move_uploaded_file($file, $url);

        return new JR(NS::getPhoto($photo), Response::HTTP_CREATED);
    }

    /**
     * @Route("/{photoId}", name="photo-delete", methods={"DELETE"})
     */
    public function delete(EntityManagerInterface $em, $photoId)
    {
        $p = $em->getRepository(Photo::class)->find($photoId);
        if (!$p) return new JR("Photo not found", Response::HTTP_NOT_FOUND);

        $em->remove($p);
        $em->flush();
        return new JR("Photo was deleted");
    }
}
