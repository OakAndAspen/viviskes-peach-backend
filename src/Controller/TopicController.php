<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Event;
use App\Entity\Topic;
use App\Service\FormService;
use App\Service\NormalizerService as NS;
use App\Service\UtilityService as US;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse as JR;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/topic")
 */
class TopicController extends AbstractController implements TokenAuthenticatedController
{
    /**
     * @Route("", name="topic-index", methods={"GET"})
     */
    public function index(Request $req, EntityManagerInterface $em)
    {
        $authUser = $req->get("authUser");
        $mode = $req->get("mode");
        $categoryId = $req->get("category");
        $eventId = $req->get("event");

        $showParent = true;

        switch ($mode) {
            case "recent":
                $topics = $em->getRepository(Topic::class)->findAll();
                usort($topics, function ($a, $b) {
                    $aLastMessage = US::getLastMessage($a)->getCreated();
                    $bLastMessage = US::getLastMessage($b)->getCreated();
                    return $aLastMessage > $bLastMessage ? -1 : 1;
                });
                $topics = array_slice($topics, 0, 10);
                break;
            case "category":
                if(!$categoryId) return new JR("Missing data", Response::HTTP_BAD_REQUEST);
                $category = $em->getRepository(Category::class)->find($categoryId);
                if(!$category) return new JR("Category not found", Response::HTTP_BAD_REQUEST);
                $topics = $category->getTopics();
                $showParent = false;
                break;
            case "event":
                if(!$eventId) return new JR("Missing data", Response::HTTP_BAD_REQUEST);
                $event = $em->getRepository(Event::class)->find($eventId);
                if(!$event) return new JR("Event not found", Response::HTTP_BAD_REQUEST);
                $topics = $event->getTopics();
                $showParent = false;
                break;
            default:
                $topics = $em->getRepository(Topic::class)->findAll();
        }

        $array = [];
        foreach ($topics as $t) array_push($array, NS::getTopic($t, $authUser, false, $showParent));
        return new JR($array);
    }

    /**
     * @Route("", name="topic-create", methods={"POST"})
     */
    public function create(Request $req, EntityManagerInterface $em)
    {
        $authUser = $req->get("authUser");
        $data = $req->get("topic");
        if (!$data) return new JR("No data", Response::HTTP_BAD_REQUEST);

        $topic = FormService::upsertTopic($em, $data);
        if (is_string($topic)) return new JR($topic, Response::HTTP_BAD_REQUEST);

        return new JR(NS::getTopic($topic, $authUser), Response::HTTP_CREATED);
    }

    /**
     * @Route("/{topicId}", name="topic-show", methods={"GET"})
     */
    public function show(Request $req, EntityManagerInterface $em, $topicId)
    {
        $authUser = $req->get("authUser");

        $topic = $em->getRepository(Topic::class)->find($topicId);
        if (!$topic) return new JR("Topic not found", Response::HTTP_NOT_FOUND);

        $topic->removeUnreadUser($authUser);
        $em->persist($topic);
        $em->flush();

        return new JR(NS::getTopic($topic, $authUser, true, true));
    }

    /**
     * @Route("/{topicId}", name="topic-update", methods={"PUT"})
     */
    public function update(Request $req, EntityManagerInterface $em, $topicId)
    {
        $authUser = $req->get("authUser");
        $data = $req->get("topic");
        if (!$data) return new JR("No data", Response::HTTP_BAD_REQUEST);

        $topic = $em->getRepository(Topic::class)->find($topicId);
        if (!$topic) return new JR("Topic not found", Response::HTTP_NOT_FOUND);

        $topic = FormService::upsertTopic($em, $data, $topic);
        if (is_string($topic)) return new JR($topic, Response::HTTP_BAD_REQUEST);

        return new JR(NS::getTopic($topic, $authUser, true, true));
    }

    /**
     * @Route("/{topicId}", name="topic-delete", methods={"DELETE"})
     */
    public function delete(EntityManagerInterface $em, $topicId)
    {
        $t = $em->getRepository(Topic::class)->find($topicId);
        if (!$t) return new JR("Topic not found", Response::HTTP_NOT_FOUND);

        $em->remove($t);
        $em->flush();
        return new JR("Topic was deleted");
    }
}
