<?php

namespace App\Controller;

use App\Entity\Folder;
use App\Service\JsonService as JS;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\JsonResponse as JR;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use DateTime;
use Exception;
use ZipArchive;

/**
 * @Route("/folders")
 */
class FolderController extends AbstractController implements TokenAuthenticatedController
{
    /**
     * @Route("", name="folder-index", methods={"GET"})
     *
     * @param EntityManagerInterface $em
     * @return JR
     */
    public function index(EntityManagerInterface $em)
    {
        $folders = $em->getRepository(Folder::class)->findAll();
        $data = [];
        foreach ($folders as $f) array_push($data, JS::getFolder($f));
        return new JR($data, Response::HTTP_OK);
    }

    /**
     * @Route("", name="folder-create", methods={"POST"})
     *
     * @param Request $req
     * @param EntityManagerInterface $em
     * @return JR
     * @throws Exception
     */
    public function create(Request $req, EntityManagerInterface $em)
    {
        $name = $req->get('name');
        $parentId = $req->get('parent');
        if (!$name) return new JR(null, Response::HTTP_BAD_REQUEST);

        $f = new Folder();
        $f->setName($name);
        $f->setCreated(new DateTime());

        if ($parentId) {
            $parent = $em->getRepository(Folder::class)->find($parentId);
            if (!$parent) return new JR(null, Response::HTTP_NOT_FOUND);
            $f->setParent($parent);
        }

        $em->persist($f);
        $em->flush();
        return new JR(JS::getFolder($f), Response::HTTP_CREATED);
    }

    /**
     * @Route("/download/{id}", name="folder-download", methods={"GET"})
     *
     * @param EntityManagerInterface $em
     * @param $id
     * @return JR|Response
     */
    public function download(EntityManagerInterface $em, $id)
    {
        $f = $em->getRepository(Folder::class)->find($id);
        if (!$f) return new JR(null, Response::HTTP_NOT_FOUND);

        $zip = new ZipArchive();
        $zipName = $f->getName() . '.zip';
        if ($zip->open($zipName, ZipArchive::CREATE) === TRUE) {
            foreach ($f->getDocuments() as $d) {
                $path = 'uploads/media/' . $d->getId() . '.' . $d->getExtension();
                $renameTo = $d->getName() . '.' . $d->getExtension();
                $zip->addFile($path, $renameTo);
            }
            $zip->close();
        }

        $response = new Response(file_get_contents($zipName));
        $response->headers->set('Content-Type', 'application/zip');
        $response->headers->set('Content-Disposition', 'attachment;filename="' . $zipName . '"');
        $response->headers->set('Content-length', filesize($zipName));

        unlink($zipName);

        return $response;
    }

    /**
     * @Route("/{id}", name="folder-show", methods={"GET"})
     *
     * @param EntityManagerInterface $em
     * @param $id
     * @return JR
     */
    public function show(EntityManagerInterface $em, $id)
    {
        $f = $em->getRepository(Folder::class)->find($id);
        if (!$f) return new JR(null, Response::HTTP_NOT_FOUND);
        return new JR(JS::getFolder($f, true), Response::HTTP_OK);
    }

    /**
     * @Route("/{id}", name="folder-update", methods={"PATCH"})
     *
     * @param Request $req
     * @param EntityManagerInterface $em
     * @param $id
     * @return JR
     */
    public function update(Request $req, EntityManagerInterface $em, $id)
    {
        $f = $em->getRepository(Folder::class)->find($id);
        if (!$f) return new JR(null, Response::HTTP_NOT_FOUND);

        $name = $req->get('name');
        $parentId = $req->get('parent');

        if ($name) $f->setName($name);
        if ($parentId) {
            $parent = $em->getRepository(Folder::class)->find($parentId);
            if (!$parent) return new JR(null, Response::HTTP_NOT_FOUND);
            $f->setParent($parent);
        }

        $em->persist($f);
        $em->flush();
        return new JR(JS::getFolder($f), Response::HTTP_OK);
    }

    /**
     * @Route("/{id}", name="folder-delete", methods={"DELETE"})
     *
     * @param EntityManagerInterface $em
     * @param $id
     * @return JR
     */
    public function delete(EntityManagerInterface $em, $id)
    {
        $f = $em->getRepository(Folder::class)->find($id);
        if (!$f) return new JR(null, Response::HTTP_NOT_FOUND);

        $em->remove($f);
        $em->flush();
        return new JR(null, Response::HTTP_NO_CONTENT);
    }


}
