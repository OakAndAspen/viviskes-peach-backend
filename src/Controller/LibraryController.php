<?php

namespace App\Controller;

use App\Entity\Book;
use App\Entity\Loan;
use App\Entity\User;
use App\Service\JsonService as JS;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse as JR;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/library")
 */
class LibraryController extends AbstractController implements TokenAuthenticatedController
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
        foreach ($books as $p) array_push($data, JS::getBook($p, $em));
        return new JR($data, Response::HTTP_OK);
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
        $b->setName($req->get("name"));

        $em->persist($b);
        $em->flush();
        return new JR(JS::getBook($b, $em), Response::HTTP_CREATED);
    }

    /**
     * @Route("/{id}", name="book-show", methods={"GET"})
     *
     * @param EntityManagerInterface $em
     * @return Response
     */
    public function show(EntityManagerInterface $em, $id)
    {
        $b = $em->getRepository(Book::class)->find($id);
        if (!$b) return new JR(null, Response::HTTP_NOT_FOUND);
        return new JR(JS::getBook($b, $em), Response::HTTP_OK);
    }

    /**
     * @Route("/{id}", name="book-update", methods={"PATCH"})
     *
     * @param Request $req
     * @param EntityManagerInterface $em
     * @return JR
     */
    public function update(Request $req, EntityManagerInterface $em, $id)
    {
        $b = $em->getRepository(Book::class)->find($id);
        if (!$b) return new JR(null, Response::HTTP_NOT_FOUND);
        if ($req->get("name")) $b->setName($req->get("name"));

        $em->persist($b);
        $em->flush();
        return new JR(JS::getBook($b, $em), Response::HTTP_OK);
    }

    /**
     * @Route("/{id}", name="book-delete", methods={"DELETE"})
     *
     * @param EntityManagerInterface $em
     * @return JR
     */
    public function delete(EntityManagerInterface $em, $id)
    {
        $b = $em->getRepository(Book::class)->find($id);
        if (!$b) return new JR(null, Response::HTTP_NOT_FOUND);

        $em->remove($b);
        $em->flush();
        return new JR(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @Route("/loan", name="book-loan", methods={"POST"})
     *
     * @param Request $req
     * @param EntityManagerInterface $em
     * @return JR
     * @throws \Exception
     */
    public function loan(Request $req, EntityManagerInterface $em)
    {
        $book = $em->getRepository(Book::class)->find($req->get("book"));
        $user = $em->getRepository(User::class)->find($req->get("user"));
        if (!$book || !$user) return new JR(null, Response::HTTP_NOT_FOUND);

        $date = new \DateTime();

        $loan = $em->getRepository(Loan::class)->findOneBy([
            "user" => $user,
            "book" => $book,
            "end" => null
        ]);

        if ($loan) $loan->setEnd($date);
        else {
            $loan = new Loan();
            $loan->setUser($user);
            $loan->setBook($book);
            $loan->setStart($date);
        }

        $em->persist($loan);
        $em->flush();
        return new JR(JS::getBook($book, $em), Response::HTTP_OK);
    }
}
