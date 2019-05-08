<?php

namespace App\Controller;

use App\Entity\Tag;
use App\Service\JsonService as JS;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse as JR;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/tags")
 */
class TagController extends AbstractController implements TokenAuthenticatedController
{
    /**
     * @Route("", name="tag-index", methods={"GET"})
     *
     * @param EntityManagerInterface $em
     * @return JR
     */
    public function index(EntityManagerInterface $em)
    {
        $tags = $em->getRepository(Tag::class)->findAll();
        $data = [];
        foreach ($tags as $p) array_push($data, JS::getTag($p));
        return new JR($data, Response::HTTP_OK);
    }

    /**
     * @Route("", name="tag-create", methods={"POST"})
     *
     * @param Request $req
     * @param EntityManagerInterface $em
     * @return JR
     */
    public function create(Request $req, EntityManagerInterface $em)
    {
        $p = new Tag();
        $p->setLabel($req->get("label"));

        $em->persist($p);
        $em->flush();
        return new JR(JS::getTag($p), Response::HTTP_CREATED);
    }

    /**
     * @Route("/{id}", name="tag-show", methods={"GET"})
     *
     * @param EntityManagerInterface $em
     * @return Response
     */
    public function show(EntityManagerInterface $em, $id)
    {
        $p = $em->getRepository(Tag::class)->find($id);
        if(!$p) return new JR(null, Response::HTTP_NOT_FOUND);
        return new JR(JS::getTag($p), Response::HTTP_OK);
    }

    /**
     * @Route("/{id}", name="tag-update", methods={"PATCH"})
     *
     * @param Request $req
     * @param EntityManagerInterface $em
     * @return JR
     */
    public function update(Request $req, EntityManagerInterface $em, $id)
    {
        $p = $em->getRepository(Tag::class)->find($id);
        if(!$p) return new JR(null, Response::HTTP_NOT_FOUND);
        if($req->get("label")) $p->setLabel($req->get("label"));

        $em->persist($p);
        $em->flush();
        return new JR(JS::getTag($p), Response::HTTP_OK);
    }

    /**
     * @Route("/{id}", name="tag-delete", methods={"DELETE"})
     *
     * @param EntityManagerInterface $em
     * @return JR
     */
    public function delete(EntityManagerInterface $em, $id)
    {
        $p = $em->getRepository(Tag::class)->find($id);
        if(!$p) return new JR(null, Response::HTTP_NOT_FOUND);

        $em->remove($p);
        $em->flush();
        return new JR(null, Response::HTTP_NO_CONTENT);
    }
}
