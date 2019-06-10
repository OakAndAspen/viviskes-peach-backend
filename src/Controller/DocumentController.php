<?php

namespace App\Controller;

use App\Entity\Document;
use App\Entity\Folder;
use App\Service\JsonService as JS;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse as JR;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use DateTime;
use Exception;

/**
 * @Route("/documents")
 */
class DocumentController extends AbstractController implements TokenAuthenticatedController
{
    /**
     * @Route("", name="document-index", methods={"GET"})
     *
     * @param EntityManagerInterface $em
     * @return JR
     */
    public function index(EntityManagerInterface $em)
    {
        $documents = $em->getRepository(Document::class)->findAll();
        $data = [];
        foreach ($documents as $d) array_push($data, JS::getDocument($d));
        return new JR($data, Response::HTTP_OK);
    }

    /**
     * @Route("", name="document-create", methods={"POST"})
     *
     * @param Request $req
     * @param EntityManagerInterface $em
     * @return JR
     * @throws Exception
     */
    public function create(Request $req, EntityManagerInterface $em)
    {
        $name = $req->get('name');
        $file = $req->files->get('file');
        $folderId = $req->get('folder');
        if (!$name || !$file) return new JR(null, Response::HTTP_BAD_REQUEST);

        $d = new Document();
        $d->setName(pathinfo($name, PATHINFO_FILENAME));
        $d->setExtension(pathinfo($name, PATHINFO_EXTENSION));
        $d->setCreated(new DateTime());

        if ($folderId) {
            $folder = $em->getRepository(Folder::class)->find($folderId);
            if (!$folder) return new JR(null, Response::HTTP_NOT_FOUND);
            $d->setFolder($folder);
        }

        $em->persist($d);
        $em->flush();

        $url = "uploads\\media\\" . $d->getId() . "." . $d->getExtension();
        move_uploaded_file($file, $url);

        return new JR(JS::getDocument($d), Response::HTTP_CREATED);
    }

    /**
     * @Route("/download/{id}", name="document-download", methods={"GET"})
     *
     * @param EntityManagerInterface $em
     * @param $id
     * @return BinaryFileResponse|JR
     */
    public function download(EntityManagerInterface $em, $id)
    {
        $d = $em->getRepository(Document::class)->find($id);
        if (!$d) return new JR(null, Response::HTTP_NOT_FOUND);

        $source = "uploads/media/" . $d->getId() . "." . $d->getExtension();
        $destination = "downloads/" . $d->getName() . "." . $d->getExtension();
        copy($source, $destination);
        return new JR(["url" => $destination]);
    }

    /**
     * @Route("/{id}", name="document-show", methods={"GET"})
     *
     * @param EntityManagerInterface $em
     * @param $id
     * @return JR
     */
    public function show(EntityManagerInterface $em, $id)
    {
        $d = $em->getRepository(Document::class)->find($id);
        if (!$d) return new JR(null, Response::HTTP_NOT_FOUND);
        return new JR(JS::getDocument($d), Response::HTTP_OK);
    }

    /**
     * @Route("/{id}", name="document-update", methods={"PATCH"})
     *
     * @param Request $req
     * @param EntityManagerInterface $em
     * @param $id
     * @return JR
     */
    public function update(Request $req, EntityManagerInterface $em, $id)
    {
        $d = $em->getRepository(Document::class)->find($id);
        if (!$d) return new JR(null, Response::HTTP_NOT_FOUND);

        $name = $req->get('name');
        $folderId = $req->get('folder');

        if ($name) $d->setName($name);
        if ($folderId) {
            $folder = $em->getRepository(Folder::class)->find($folderId);
            if (!$folder) return new JR(null, Response::HTTP_NOT_FOUND);
            $d->setFolder($folder);
        }

        $em->persist($d);
        $em->flush();
        return new JR(JS::getDocument($d), Response::HTTP_OK);
    }

    /**
     * @Route("/{id}", name="document-delete", methods={"DELETE"})
     *
     * @param EntityManagerInterface $em
     * @param $id
     * @return JR
     */
    public function delete(EntityManagerInterface $em, $id)
    {
        $d = $em->getRepository(Document::class)->find($id);
        if (!$d) return new JR(null, Response::HTTP_NOT_FOUND);

        $em->remove($d);
        $em->flush();
        return new JR(null, Response::HTTP_NO_CONTENT);
    }
}
