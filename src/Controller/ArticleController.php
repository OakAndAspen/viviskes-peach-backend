<?php

namespace App\Controller;

use App\Entity\Article;
use App\Service\JsonService as JS;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse as JR;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/articles")
 */
class ArticleController extends AbstractController implements TokenAuthenticatedController
{
    /**
     * @Route("", name="article-index", methods={"GET"})
     *
     * @param EntityManagerInterface $em
     * @return JR
     */
    public function index(EntityManagerInterface $em)
    {
        $articles = $em->getRepository(Article::class)->findAll();
        $data = [];
        foreach ($articles as $a) array_push($data, JS::getArticle($a));
        return new JR($data, Response::HTTP_OK);
    }

    /**
     * @Route("", name="article-create", methods={"POST"})
     *
     * @param Request $req
     * @param EntityManagerInterface $em
     * @return JR
     */
    public function create(Request $req, EntityManagerInterface $em)
    {
        $a = new Article();
        $a->setTitle($req->get("title"));
        $a->setAuthor($req->get("user"));
        $a->setCreated(new \DateTime());
        $a->setEdited(new \DateTime());
        $a->setContent($req->get("content"));

        $em->persist($a);
        $em->flush();
        return new JR(JS::getArticle($a), Response::HTTP_CREATED);
    }

    /**
     * @Route("/{id}", name="article-show", methods={"GET"})
     *
     * @param EntityManagerInterface $em
     * @return Response
     */
    public function show(EntityManagerInterface $em, $id)
    {
        $a = $em->getRepository(Article::class)->find($id);
        if(!$a) return new JR(null, Response::HTTP_NOT_FOUND);
        return new JR(JS::getArticle($a, true), Response::HTTP_OK);
    }

    /**
     * @Route("/{id}", name="article-update", methods={"PATCH"})
     *
     * @param Request $req
     * @param EntityManagerInterface $em
     * @return JR
     */
    public function update(Request $req, EntityManagerInterface $em, $id)
    {
        $a = $em->getRepository(Article::class)->find($id);
        if(!$a) return new JR(null, Response::HTTP_NOT_FOUND);
        if($req->get("title")) $a->setTitle($req->get("title"));
        if($req->get("content")) $a->setContent($req->get("content"));
        $a->setEdited(new \DateTime());

        $em->persist($a);
        $em->flush();
        return new JR(JS::getArticle($a, true), Response::HTTP_OK);
    }

    /**
     * @Route("/{id}", name="article-delete", methods={"DELETE"})
     *
     * @param EntityManagerInterface $em
     * @return JR
     */
    public function delete(EntityManagerInterface $em, $id)
    {
        $a = $em->getRepository(Article::class)->find($id);
        if(!$a) return new JR(null, Response::HTTP_NOT_FOUND);

        $em->remove($a);
        $em->flush();
        return new JR(null, Response::HTTP_NO_CONTENT);
    }
}
