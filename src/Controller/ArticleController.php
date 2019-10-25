<?php

namespace App\Controller;

use App\Entity\Article;
use App\Service\FormService;
use App\Service\NormalizerService as NS;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse as JR;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/article")
 */
class ArticleController extends AbstractController implements TokenAuthenticatedController
{
    /**
     * @Route("", name="article-index", methods={"GET"})
     */
    public function index(EntityManagerInterface $em)
    {
        $articles = $em->getRepository(Article::class)->findAll();
        $array = [];
        foreach ($articles as $a) array_push($array, NS::getArticle($a));
        return new JR($array);
    }

    /**
     * @Route("", name="article-create", methods={"POST"})
     */
    public function create(Request $req, EntityManagerInterface $em)
    {
        $authUser = $req->get("authUser");
        $data = $req->get("article");
        if (!$data) return new JR("No data", Response::HTTP_BAD_REQUEST);

        $data['author'] = $authUser->getId();
        $article = FormService::upsertArticle($em, $data);
        if (is_string($article)) return new JR($article, Response::HTTP_BAD_REQUEST);

        return new JR(NS::getArticle($article), Response::HTTP_CREATED);
    }

    /**
     * @Route("/{articleId}", name="article-show", methods={"GET"})
     */
    public function show(EntityManagerInterface $em, $articleId)
    {
        $article = $em->getRepository(Article::class)->find($articleId);
        if (!$article) return new JR("Article not found", Response::HTTP_NOT_FOUND);
        return new JR(NS::getArticle($article, true));
    }

    /**
     * @Route("/{articleId}", name="article-update", methods={"PUT"})
     */
    public function update(Request $req, EntityManagerInterface $em, $articleId)
    {
        $data = $req->get("article");
        if (!$data) return new JR("No data", Response::HTTP_BAD_REQUEST);

        $article = $em->getRepository(Article::class)->find($articleId);
        if (!$article) return new JR("Article not found", Response::HTTP_NOT_FOUND);

        $article = FormService::upsertArticle($em, $data, $article);
        if (is_string($article)) return new JR($article, Response::HTTP_BAD_REQUEST);

        return new JR(NS::getArticle($article, true));
    }

    /**
     * @Route("/{articleId}", name="article-delete", methods={"DELETE"})
     */
    public function delete(EntityManagerInterface $em, $articleId)
    {
        $article = $em->getRepository(Article::class)->find($articleId);
        if (!$article) return new JR("Article not found", Response::HTTP_NOT_FOUND);

        $em->remove($article);
        $em->flush();
        return new JR("Article was deleted");
    }
}
