<?php

namespace App\Controller;

use App\Entity\Folder;
use App\Service\FormService;
use App\Service\NormalizerService as NS;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse as JR;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use ZipArchive;

/**
 * @Route("/folder")
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
        $array = [];
        foreach ($folders as $f) array_push($array, NS::getFolder($f));
        return new JR($array);
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
        $data = $req->get("folder");
        if (!$data) return new JR("No data", Response::HTTP_BAD_REQUEST);

        $folder = FormService::upsertFolder($em, $data);
        if (is_string($folder)) return new JR($folder, Response::HTTP_BAD_REQUEST);

        return new JR(NS::getFolder($folder), Response::HTTP_CREATED);
    }

    /**
     * @Route("/download/{folderId}", name="folder-download", methods={"GET"})
     *
     * @param EntityManagerInterface $em
     * @param $folderId
     * @return JR|Response
     */
    public function download(EntityManagerInterface $em, $folderId)
    {
        $f = $em->getRepository(Folder::class)->find($folderId);
        if (!$f) return new JR("Folder not found", Response::HTTP_NOT_FOUND);

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
     * @Route("/{folderId}", name="folder-show", methods={"GET"})
     *
     * @param EntityManagerInterface $em
     * @param $folderId
     * @return JR
     */
    public function show(EntityManagerInterface $em, $folderId)
    {
        $f = $em->getRepository(Folder::class)->find($folderId);
        if (!$f) return new JR("Folder not found", Response::HTTP_NOT_FOUND);
        return new JR(NS::getFolder($f, true));
    }

    /**
     * @Route("/{folderId}", name="folder-update", methods={"PUT"})
     *
     * @param Request $req
     * @param EntityManagerInterface $em
     * @param $folderId
     * @return JR
     */
    public function update(Request $req, EntityManagerInterface $em, $folderId)
    {
        $data = $req->get("folder");
        if (!$data) return new JR("No data", Response::HTTP_BAD_REQUEST);

        $folder = $em->getRepository(Folder::class)->find($folderId);
        if (!$folder) return new JR("Folder not found", Response::HTTP_NOT_FOUND);

        $folder = FormService::upsertFolder($em, $data, $folder);
        if (is_string($folder)) return new JR($folder, Response::HTTP_BAD_REQUEST);

        return new JR(NS::getFolder($folder));
    }

    /**
     * @Route("/{folderId}", name="folder-delete", methods={"DELETE"})
     *
     * @param EntityManagerInterface $em
     * @param $folderId
     * @return JR
     */
    public function delete(EntityManagerInterface $em, $folderId)
    {
        $f = $em->getRepository(Folder::class)->find($folderId);
        if (!$f) return new JR("Folder not found", Response::HTTP_NOT_FOUND);

        $em->remove($f);
        $em->flush();
        return new JR("Folder was deleted");
    }


}
