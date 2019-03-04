<?php

namespace App\Controller;

use App\Entity\Partner;
use App\Service\JsonService as JS;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse as JR;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/partners")
 */
class PartnerController extends AbstractController implements TokenAuthenticatedController
{
    /**
     * @Route("", name="partner-index", methods={"GET"})
     *
     * @param EntityManagerInterface $em
     * @return JR
     */
    public function index(EntityManagerInterface $em)
    {
        $partners = $em->getRepository(Partner::class)->findAll();
        $data = [];
        foreach ($partners as $p) array_push($data, JS::getPartner($p));
        return new JR($data, Response::HTTP_OK);
    }

    /**
     * @Route("", name="partner-create", methods={"POST"})
     *
     * @param Request $req
     * @param EntityManagerInterface $em
     * @return JR
     */
    public function create(Request $req, EntityManagerInterface $em)
    {
        $p = new Partner();
        $p->setLabel($req->get("label"));
        $p->setUrl($req->get("url"));

        $em->persist($p);
        $em->flush();
        return new JR(JS::getPartner($p), Response::HTTP_CREATED);
    }

    /**
     * @Route("/{id}", name="partner-show", methods={"GET"})
     *
     * @param EntityManagerInterface $em
     * @return Response
     */
    public function show(EntityManagerInterface $em, $id)
    {
        $p = $em->getRepository(Partner::class)->find($id);
        if(!$p) return new JR(null, Response::HTTP_NOT_FOUND);
        return new JR(JS::getPartner($p), Response::HTTP_OK);
    }

    /**
     * @Route("/{id}", name="partner-update", methods={"PATCH"})
     *
     * @param Request $req
     * @param EntityManagerInterface $em
     * @return JR
     */
    public function update(Request $req, EntityManagerInterface $em, $id)
    {
        $p = $em->getRepository(Partner::class)->find($id);
        if(!$p) return new JR(null, Response::HTTP_NOT_FOUND);
        if($req->get("label")) $p->setLabel($req->get("label"));
        if($req->get("url")) $p->setUrl($req->get("url"));

        $em->persist($p);
        $em->flush();
        return new JR(JS::getPartner($p), Response::HTTP_OK);
    }

    /**
     * @Route("/{id}", name="partner-delete", methods={"DELETE"})
     *
     * @param EntityManagerInterface $em
     * @return JR
     */
    public function delete(EntityManagerInterface $em, $id)
    {
        $p = $em->getRepository(Partner::class)->find($id);
        if(!$p) return new JR(null, Response::HTTP_NOT_FOUND);

        $em->remove($p);
        $em->flush();
        return new JR(null, Response::HTTP_NO_CONTENT);
    }
}
