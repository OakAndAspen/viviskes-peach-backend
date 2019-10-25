<?php

namespace App\Controller;

use App\Entity\Tag;
use App\Service\FormService;
use App\Service\NormalizerService as NS;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse as JR;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/tag")
 */
class TagController extends AbstractController implements TokenAuthenticatedController
{
    /**
     * @Route("", name="tag-index", methods={"GET"})
     */
    public function index(EntityManagerInterface $em)
    {
        $tags = $em->getRepository(Tag::class)->findAll();
        $array = [];
        foreach ($tags as $p) array_push($array, NS::getTag($p));
        return new JR($array);
    }

    /**
     * @Route("", name="tag-create", methods={"POST"})
     */
    public function create(Request $req, EntityManagerInterface $em)
    {
        $data = $req->get("tag");
        if (!$data) return new JR("No data", Response::HTTP_BAD_REQUEST);

        $tag = FormService::upsertTag($em, $data);
        if (is_string($tag)) return new JR($tag, Response::HTTP_BAD_REQUEST);

        return new JR(NS::getTag($tag), Response::HTTP_CREATED);
    }

    /**
     * @Route("/{tagId}", name="tag-show", methods={"GET"})
     */
    public function show(EntityManagerInterface $em, $tagId)
    {
        $tag = $em->getRepository(Tag::class)->find($tagId);
        if(!$tag) return new JR("Tag not found", Response::HTTP_NOT_FOUND);
        return new JR(NS::getTag($tag));
    }

    /**
     * @Route("/{tagId}", name="tag-update", methods={"PUT"})
     */
    public function update(Request $req, EntityManagerInterface $em, $tagId)
    {
        $data = $req->get("tag");
        if (!$data) return new JR("No data", Response::HTTP_BAD_REQUEST);

        $tag = $em->getRepository(Tag::class)->find($tagId);
        if(!$tag) return new JR("Tag not found", Response::HTTP_NOT_FOUND);

        $tag = FormService::upsertTag($em, $data, $tag);
        if (is_string($tag)) return new JR($tag, Response::HTTP_BAD_REQUEST);

        return new JR(NS::getTag($tag));
    }

    /**
     * @Route("/{tagId}", name="tag-delete", methods={"DELETE"})
     */
    public function delete(EntityManagerInterface $em, $tagId)
    {
        $tag = $em->getRepository(Tag::class)->find($tagId);
        if(!$tag) return new JR("Tag not found", Response::HTTP_NOT_FOUND);

        $em->remove($tag);
        $em->flush();
        return new JR("Tag was deleted");
    }
}
