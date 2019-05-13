<?php

namespace App\Controller;

use App\Entity\Category;
use App\Service\JsonService as JS;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse as JR;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/category")
 *
 */
class CategoryController extends AbstractController implements TokenAuthenticatedController
{
    /**
     * @Route("", name="category-index", methods={"GET"})
     *
     * @param Request $req
     * @param EntityManagerInterface $em
     * @return JR
     */
    public function index(Request $req, EntityManagerInterface $em)
    {
        $category = $em->getRepository(Category::class)->findAll();
        $data = [];
        foreach ($category as $c) array_push($data, JS::getCategory($c, $req->get("user")));
        return new JR($data, Response::HTTP_OK);
    }

    /**
     * @Route("", name="category-create", methods={"POST"})
     *
     * @param Request $req
     * @param EntityManagerInterface $em
     * @return JR
     */
    public function create(Request $req, EntityManagerInterface $em)
    {
        $c = new Category();
        $c->setLabel($req->get("label"));

        $em->persist($c);
        $em->flush();
        return new JR(JS::getCategory($c, $req->get("user")), Response::HTTP_CREATED);
    }

    /**
     * @Route("/{id}", name="category-show", methods={"GET"})
     *
     * @param Request $req
     * @param EntityManagerInterface $em
     * @param $id
     * @return JR
     */
    public function show(Request $req, EntityManagerInterface $em, $id)
    {
        $c = $em->getRepository(Category::class)->find($id);
        if (!$c) return new JR(null, Response::HTTP_NOT_FOUND);
        return new JR(JS::getCategory($c, $req->get("user"), true), Response::HTTP_OK);
    }

    /**
     * @Route("/{id}", name="category-update", methods={"PATCH"})
     *
     * @param Request $req
     * @param EntityManagerInterface $em
     * @return JR
     */
    public function update(Request $req, EntityManagerInterface $em, $id)
    {
        $c = $em->getRepository(Category::class)->find($id);
        if (!$c) return new JR(null, Response::HTTP_NOT_FOUND);
        if ($req->get("label")) $c->setLabel($req->get("label"));

        $em->persist($c);
        $em->flush();
        return new JR(JS::getCategory($c, $req->get("user"), true), Response::HTTP_OK);
    }

    /**
     * @Route("/{id}", name="category-delete", methods={"DELETE"})
     *
     * @param EntityManagerInterface $em
     * @return JR
     */
    public function delete(EntityManagerInterface $em, $id)
    {
        $c = $em->getRepository(Category::class)->find($id);
        if (!$c) return new JR(null, Response::HTTP_NOT_FOUND);

        $em->remove($c);
        $em->flush();
        return new JR(null, Response::HTTP_NO_CONTENT);
    }
}
