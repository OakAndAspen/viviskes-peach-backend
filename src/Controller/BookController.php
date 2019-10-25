<?php

namespace App\Controller;

use App\Entity\Book;
use App\Service\FormService;
use App\Service\NormalizerService as NS;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse as JR;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/book")
 */
class BookController extends AbstractController implements TokenAuthenticatedController
{
    /**
     * @Route("", name="book-index", methods={"GET"})
     */
    public function index(EntityManagerInterface $em)
    {
        $books = $em->getRepository(Book::class)->findAll();
        $array = [];
        foreach ($books as $p) array_push($array, NS::getBook($p));
        return new JR($array);
    }

    /**
     * @Route("", name="book-create", methods={"POST"})
     */
    public function create(Request $req, EntityManagerInterface $em)
    {
        $data = $req->get("book");
        if (!$data) return new JR("No data", Response::HTTP_BAD_REQUEST);

        $book = FormService::upsertBook($em, $data);
        if (is_string($book)) return new JR($book, Response::HTTP_BAD_REQUEST);

        return new JR(NS::getBook($book, true), Response::HTTP_CREATED);
    }

    /**
     * @Route("/{bookId}", name="book-show", methods={"GET"})
     */
    public function show(EntityManagerInterface $em, $bookId)
    {
        $b = $em->getRepository(Book::class)->find($bookId);
        if (!$b) return new JR("Book not found", Response::HTTP_NOT_FOUND);
        return new JR(NS::getBook($b, true));
    }

    /**
     * @Route("/{bookId}", name="book-update", methods={"PUT"})
     */
    public function update(Request $req, EntityManagerInterface $em, $bookId)
    {

        $data = $req->get("book");
        if (!$data) return new JR("No data", Response::HTTP_BAD_REQUEST);

        $book = $em->getRepository(Book::class)->find($bookId);
        if (!$book) return new JR("Book not found", Response::HTTP_NOT_FOUND);

        $book = FormService::upsertBook($em, $data, $book);
        if (is_string($book)) return new JR($book, Response::HTTP_BAD_REQUEST);

        return new JR(NS::getBook($book, true));
    }

    /**
     * @Route("/{bookId}", name="book-delete", methods={"DELETE"})
     */
    public function delete(EntityManagerInterface $em, $bookId)
    {
        $b = $em->getRepository(Book::class)->find($bookId);
        if (!$b) return new JR( "Book not found", Response::HTTP_NOT_FOUND);

        $em->remove($b);
        $em->flush();
        return new JR("Book was deleted");
    }
}
