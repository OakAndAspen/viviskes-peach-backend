<?php

namespace App\Controller;

use App\Entity\Message;
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
 * @Route("/message")
 */
class MessageController extends AbstractController implements TokenAuthenticatedController
{
    /**
     * @Route("", name="message-index", methods={"GET"})
     */
    public function index(EntityManagerInterface $em)
    {
        $messages = $em->getRepository(Message::class)->findAll();
        $array = [];
        foreach ($messages as $m) array_push($array, NS::getMessage($m));
        return new JR($array);
    }

    /**
     * @Route("", name="message-create", methods={"POST"})
     */
    public function create(Request $req, EntityManagerInterface $em)
    {
        $authUser = $req->get("authUser");
        $data = $req->get("message");
        if (!$data) return new JR("No data", Response::HTTP_BAD_REQUEST);

        $data["author"] = $authUser->getId();

        $message = FormService::upsertMessage($em, $data);
        if (is_string($message)) return new JR($message, Response::HTTP_BAD_REQUEST);

        $topic = $message->getTopic();
        foreach ($em->getRepository(User::class)->findAll() as $u) {
            if ($u !== $authUser) $topic->addUnreadUser($u);
        }
        $em->persist($topic);
        $em->flush();

        return new JR(NS::getMessage($message, true), Response::HTTP_CREATED);
    }

    /**
     * @Route("/{messageId}", name="message-show", methods={"GET"})
     */
    public function show(EntityManagerInterface $em, $messageId)
    {
        $m = $em->getRepository(Message::class)->find($messageId);
        if (!$m) return new JR("Message not found", Response::HTTP_NOT_FOUND);
        return new JR(NS::getMessage($m, true));
    }

    /**
     * @Route("/{messageId}", name="message-update", methods={"PUT"})
     */
    public function update(Request $req, EntityManagerInterface $em, $messageId)
    {
        $authUser = $req->get("authUser");
        $data = $req->get("message");
        if (!$data) return new JR("No data", Response::HTTP_BAD_REQUEST);

        $message = $em->getRepository(Message::class)->find($messageId);
        if (!$message) return new JR("Message not found", Response::HTTP_NOT_FOUND);

        $message = FormService::upsertMessage($em, $data, $message);
        if (is_string($message)) return new JR($message, Response::HTTP_BAD_REQUEST);

        $topic = $message->getTopic();
        foreach ($em->getRepository(User::class)->findAll() as $u) {
            if ($u !== $authUser) $topic->addUnreadUser($u);
        }
        $em->persist($topic);
        $em->flush();

        return new JR(NS::getMessage($message, true));
    }

    /**
     * @Route("/{messageId}", name="message-delete", methods={"DELETE"})
     */
    public function delete(EntityManagerInterface $em, $messageId)
    {
        $p = $em->getRepository(Message::class)->find($messageId);
        if (!$p) return new JR("Message not found", Response::HTTP_NOT_FOUND);

        $em->remove($p);
        $em->flush();
        return new JR("Message", Response::HTTP_NO_CONTENT);
    }
}
