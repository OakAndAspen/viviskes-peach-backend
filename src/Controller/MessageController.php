<?php

namespace App\Controller;

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

/**
 * @Route("/message")
 */
class MessageController extends AbstractController implements TokenAuthenticatedController
{
    /**
     * @Route("", name="message-index", methods={"GET"})
     *
     * @param EntityManagerInterface $em
     * @return JR
     */
    public function index(EntityManagerInterface $em)
    {
        $messages = $em->getRepository(Message::class)->findAll();
        $data = [];
        foreach ($messages as $m) array_push($data, JS::getMessage($m));
        return new JR($data, Response::HTTP_OK);
    }

    /**
     * @Route("", name="message-create", methods={"POST"})
     *
     * @param Request $req
     * @param EntityManagerInterface $em
     * @return JR
     * @throws \Exception
     */
    public function create(Request $req, EntityManagerInterface $em)
    {
        $authorId = $req->get('author');
        $topicId = $req->get('topic');
        $content = $req->get('content');
        if(!$authorId || !$topicId || !$content) return new JR(null, Response::HTTP_BAD_REQUEST);

        $author = $em->getRepository(User::class)->find($authorId);
        $topic = $em->getRepository(Topic::class)->find($topicId);
        if(!$author || !$topic) return new JR(null, Response::HTTP_NOT_FOUND);

        $m = new Message();
        $m->setAuthor($author);
        $m->setTopic($topic);
        $m->setContent($content);
        $m->setCreated(new \DateTime());
        $m->setEdited(new \DateTime());

        $em->persist($m);
        $em->flush();
        return new JR(JS::getMessage($m), Response::HTTP_CREATED);
    }

    /**
     * @Route("/{id}", name="message-show", methods={"GET"})
     *
     * @param EntityManagerInterface $em
     * @return Response
     */
    public function show(EntityManagerInterface $em, $id)
    {
        $m = $em->getRepository(Message::class)->find($id);
        if(!$m) return new JR(null, Response::HTTP_NOT_FOUND);
        return new JR(JS::getMessage($m), Response::HTTP_OK);
    }

    /**
     * @param Request $req
     * @param EntityManagerInterface $em
     * @param $id
     * @return JR
     * @throws \Exception
     */
    public function update(Request $req, EntityManagerInterface $em, $id)
    {
        $m = $em->getRepository(Message::class)->find($id);
        if(!$m) return new JR(null, Response::HTTP_NOT_FOUND);

        $content = $req->get('content');
        if($content) $m->setContent($content);
        $m->setEdited(new \DateTime());

        $em->persist($m);
        $em->flush();
        return new JR(JS::getMessage($m), Response::HTTP_OK);
    }

    /**
     * @Route("/{id}", name="message-delete", methods={"DELETE"})
     *
     * @param EntityManagerInterface $em
     * @return JR
     */
    public function delete(EntityManagerInterface $em, $id)
    {
        $p = $em->getRepository(Message::class)->find($id);
        if(!$p) return new JR(null, Response::HTTP_NOT_FOUND);

        $em->remove($p);
        $em->flush();
        return new JR(null, Response::HTTP_NO_CONTENT);
    }
}
