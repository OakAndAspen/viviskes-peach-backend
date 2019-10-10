<?php

namespace App\Controller;

use App\Entity\Book;
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
     *
     * @param EntityManagerInterface $em
     * @return JR
     */
    public function index(EntityManagerInterface $em)
    {
        $books = $em->getRepository(Book::class)->findAll();
        $data = [];
        foreach ($books as $p) array_push($data, NS::getBook($p));
        return new JR($data);
    }

    /**
     * @Route("", name="book-create", methods={"POST"})
     *
     * @param Request $req
     * @param EntityManagerInterface $em
     * @return JR
     */
    public function create(Request $req, EntityManagerInterface $em)
    {
        $b = new Book();


        return new JR(NS::getBook($b, $em), Response::HTTP_CREATED);
    }

    /**
     * @Route("/{bookId}", name="book-show", methods={"GET"})
     *
     * @param EntityManagerInterface $em
     * @param $bookId
     * @return Response
     */
    public function show(EntityManagerInterface $em, $bookId)
    {
        $b = $em->getRepository(Book::class)->find($bookId);
        if (!$b) return new JR("Book not found", Response::HTTP_NOT_FOUND);
        return new JR(NS::getBook($b, $em));
    }

    /**
     * @Route("/{bookId}", name="book-update", methods={"PUT"})
     *
     * @param Request $req
     * @param EntityManagerInterface $em
     * @param $bookId
     * @return JR
     */
    public function update(Request $req, EntityManagerInterface $em, $bookId)
    {
        $b = $em->getRepository(Book::class)->find($bookId);
        if (!$b) return new JR("Book not found", Response::HTTP_NOT_FOUND);
        if ($req->get("name")) $b->setName($req->get("name"));

        $em->persist($b);
        $em->flush();
        return new JR(NS::getBook($b, $em));
    }

    /**
     * @Route("/{bookId}", name="book-delete", methods={"DELETE"})
     *
     * @param EntityManagerInterface $em
     * @param $bookId
     * @return JR
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
