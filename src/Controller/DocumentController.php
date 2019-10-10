<?php

namespace App\Controller;

use App\Entity\Document;
use App\Service\FormService;
use App\Service\NormalizerService as NS;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse as JR;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/document")
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
        $array = [];
        foreach ($documents as $d) array_push($array, NS::getDocument($d));
        return new JR($array);
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
        $data = $req->get("document");
        $file = $req->files->get('file');
        if (!$data || !$file) return new JR("No data", Response::HTTP_BAD_REQUEST);

        $document = FormService::upsertDocument($em, $data);
        if (is_string($document)) return new JR($document, Response::HTTP_BAD_REQUEST);

        $filename = $document->getId() . "." . $document->getExtension();
        $path = "uploads" . DIRECTORY_SEPARATOR . "media" . DIRECTORY_SEPARATOR . $filename;
        move_uploaded_file($file, $path);

        return new JR(NS::getDocument($document), Response::HTTP_CREATED);
    }

    /**
     * @Route("/download/{documentId}", name="document-download", methods={"GET"})
     *
     * @param EntityManagerInterface $em
     * @param $documentId
     * @return BinaryFileResponse|JR
     */
    public function download(EntityManagerInterface $em, $documentId)
    {
        $document = $em->getRepository(Document::class)->find($documentId);
        if (!$document) return new JR("Document not found", Response::HTTP_NOT_FOUND);

        $source = "uploads/media/" . $document->getId() . "." . $document->getExtension();
        $destination = "downloads/" . $document->getName() . "." . $document->getExtension();
        copy($source, $destination);
        return new JR(["url" => $destination]);
    }

    /**
     * @Route("/{documentId}", name="document-show", methods={"GET"})
     *
     * @param EntityManagerInterface $em
     * @param $documentId
     * @return JR
     */
    public function show(EntityManagerInterface $em, $documentId)
    {
        $document = $em->getRepository(Document::class)->find($documentId);
        if (!$document) return new JR("Document not found", Response::HTTP_NOT_FOUND);
        return new JR(NS::getDocument($document));
    }

    /**
     * @Route("/{documentId}", name="document-update", methods={"PUT"})
     *
     * @param Request $req
     * @param EntityManagerInterface $em
     * @param $documentId
     * @return JR
     */
    public function update(Request $req, EntityManagerInterface $em, $documentId)
    {
        $data = $req->get("document");
        if (!$data) return new JR("No data", Response::HTTP_BAD_REQUEST);

        $document = $em->getRepository(Document::class)->find($documentId);
        if (!$document) return new JR("Document not found", Response::HTTP_NOT_FOUND);

        $document = FormService::upsertDocument($em, $data, $document);
        if (is_string($document)) return new JR($document, Response::HTTP_BAD_REQUEST);

        return new JR(NS::getDocument($document));
    }

    /**
     * @Route("/{documentId}", name="document-delete", methods={"DELETE"})
     *
     * @param EntityManagerInterface $em
     * @param $documentId
     * @return JR
     */
    public function delete(EntityManagerInterface $em, $documentId)
    {
        $document = $em->getRepository(Document::class)->find($documentId);
        if (!$document) return new JR("Document not found", Response::HTTP_NOT_FOUND);

        $em->remove($document);
        $em->flush();
        return new JR("Document was deleted", Response::HTTP_NO_CONTENT);
    }
}
