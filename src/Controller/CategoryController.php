<?php

namespace App\Controller;

use App\Entity\Category;
use App\Service\FormService;
use App\Service\NormalizerService as NS;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse as JR;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/category")
 */
class CategoryController extends AbstractController implements TokenAuthenticatedController
{
    /**
     * @Route("", name="category-index", methods={"GET"})
     */
    public function index(Request $req, EntityManagerInterface $em)
    {
        $category = $em->getRepository(Category::class)->findAll();

        $array = [];
        foreach ($category as $c) array_push($array, NS::getCategory($c, $req->get("authUser")));
        return new JR($array);
    }

    /**
     * @Route("", name="category-create", methods={"POST"})
     */
    public function create(Request $req, EntityManagerInterface $em)
    {
        $data = $req->get("category");
        if (!$data) return new JR("No data", Response::HTTP_BAD_REQUEST);

        $category = FormService::upsertCategory($em, $data);
        if (is_string($category)) return new JR($category, Response::HTTP_BAD_REQUEST);

        return new JR(NS::getCategory($category, $req->get("authUser")), Response::HTTP_CREATED);
    }

    /**
     * @Route("/{categoryId}", name="category-show", methods={"GET"})
     */
    public function show(Request $req, EntityManagerInterface $em, $categoryId)
    {
        $category = $em->getRepository(Category::class)->find($categoryId);
        if (!$category) return new JR("Category not found", Response::HTTP_NOT_FOUND);

        return new JR(NS::getCategory($category, $req->get("authUser"), true));
    }

    /**
     * @Route("/{categoryId}", name="category-update", methods={"PUT"})
     */
    public function update(Request $req, EntityManagerInterface $em, $categoryId)
    {
        $data = $req->get("category");
        if (!$data) return new JR("No data", Response::HTTP_BAD_REQUEST);

        $category = $em->getRepository(Category::class)->find($categoryId);
        if (!$category) return new JR("Category not found", Response::HTTP_NOT_FOUND);

        $category = FormService::upsertCategory($em, $data, $category);
        if (is_string($category)) return new JR($category, Response::HTTP_BAD_REQUEST);

        return new JR(NS::getCategory($category, $req->get("authUser"), true));
    }

    /**
     * @Route("/{categoryId}", name="category-delete", methods={"DELETE"})
     */
    public function delete(EntityManagerInterface $em, $categoryId)
    {
        $c = $em->getRepository(Category::class)->find($categoryId);
        if (!$c) return new JR("Category not found", Response::HTTP_NOT_FOUND);

        $em->remove($c);
        $em->flush();
        return new JR("Category was deleted");
    }
}
