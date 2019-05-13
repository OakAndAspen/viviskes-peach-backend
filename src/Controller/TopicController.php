<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Event;
use App\Entity\Message;
use App\Entity\Topic;
use App\Entity\User;
use App\Service\JsonService as JS;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse as JR;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Exception;
use DateTime;

/**
 * @Route("/topic")
 */
class TopicController extends AbstractController implements TokenAuthenticatedController
{
    /**
     * @Route("", name="topic-index", methods={"GET"})
     *
     * @param Request $req
     * @param EntityManagerInterface $em
     * @return JR
     */
    public function index(Request $req, EntityManagerInterface $em)
    {
        $topic = $em->getRepository(Topic::class)->findAll();
        $data = [];
        foreach ($topic as $t) array_push($data, JS::getTopic($t, $req->get("user")));
        return new JR($data, Response::HTTP_OK);
    }

    /**
     * @Route("", name="topic-create", methods={"POST"})
     *
     * @param Request $req
     * @param EntityManagerInterface $em
     * @return JR
     * @throws Exception
     */
    public function create(Request $req, EntityManagerInterface $em)
    {
        $eventId = $req->get('event');
        $catId = $req->get('category');
        $title = $req->get('title');
        $message = $req->get('message');

        if((!$eventId && !$catId) || !$title || !$message) {
            return new JR(null, Response::HTTP_BAD_REQUEST);
        }

        // Create the topic
        $t = new Topic();
        $t->setTitle($title);
        $t->setPinned(false);

        if($eventId) {
            $event = $em->getRepository(Event::class)->find($eventId);
            if(!$event) return new JR(null, Response::HTTP_NOT_FOUND);
            $t->setEvent($event);
        } else {
            $category = $em->getRepository(Category::class)->find($catId);
            if(!$category) return new JR(null, Response::HTTP_NOT_FOUND);
            $t->setCategory($category);
        }

        // Notify the users
        foreach ($em->getRepository(User::class)->findAll() as $u) {
            if($u !== $req->get("user")) {
                $t->addUnreadUser($u);
            }
        }

        $em->persist($t);
        $em->flush();

        // Create the message
        $m = new Message();
        $m->setTopic($t);
        $m->setContent($message);
        $m->setAuthor($req->get("user"));
        $m->setCreated(new DateTime());
        $m->setEdited(new DateTime());
        $em->persist($m);
        $em->flush();

        return new JR(JS::getTopic($t, $req->get("user")), Response::HTTP_CREATED);
    }

    /**
     * @Route("/{id}", name="topic-show", methods={"GET"})
     *
     * @param Request $req
     * @param EntityManagerInterface $em
     * @param $id
     * @return JR
     */
    public function show(Request $req, EntityManagerInterface $em, $id)
    {
        $t = $em->getRepository(Topic::class)->find($id);
        if(!$t) return new JR(null, Response::HTTP_NOT_FOUND);

        $t->removeUnreadUser($req->get("user"));
        $em->persist($t);
        $em->flush();

        return new JR(JS::getTopic($t, $req->get("user"), true), Response::HTTP_OK);
    }

    /**
     * @Route("/{id}", name="topic-update", methods={"PATCH"})
     *
     * @param Request $req
     * @param EntityManagerInterface $em
     * @param $id
     * @return JR
     */
    public function update(Request $req, EntityManagerInterface $em, $id)
    {
        $eventId = $req->get('event');
        $catId = $req->get('category');
        $title = $req->get('title');
        $pinned = $req->get('pinned');

        $t = $em->getRepository(Topic::class)->find($id);
        if(!$t) return new JR(null, Response::HTTP_NOT_FOUND);

        if($eventId) {
            $event = $em->getRepository(Event::class)->find($eventId);
            if(!$event) return new JR(null, Response::HTTP_NOT_FOUND);
            $t->setEvent($event);
            $t->setCategory(null);
        }

        if($catId) {
            $category = $em->getRepository(Category::class)->find($catId);
            if(!$category) return new JR(null, Response::HTTP_NOT_FOUND);
            $t->setCategory($category);
            $t->setEvent(null);
        }

        if($title) $t->setTitle($title);
        if($pinned) $t->setPinned($pinned);

        $em->persist($t);
        $em->flush();
        return new JR(JS::getTopic($t, $req->get("user"), true), Response::HTTP_OK);
    }

    /**
     * @Route("/{id}", name="topic-delete", methods={"DELETE"})
     *
     * @param EntityManagerInterface $em
     * @param $id
     * @return JR
     */
    public function delete(EntityManagerInterface $em, $id)
    {
        $t = $em->getRepository(Topic::class)->find($id);
        if(!$t) return new JR(null, Response::HTTP_NOT_FOUND);

        $em->remove($t);
        $em->flush();
        return new JR(null, Response::HTTP_NO_CONTENT);
    }
}
