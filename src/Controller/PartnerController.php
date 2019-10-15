<?php

namespace App\Controller;

use App\Entity\Partner;
use App\Service\FormService;
use App\Service\NormalizerService as NS;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse as JR;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/partner")
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
        $array = [];
        foreach ($partners as $p) array_push($array, NS::getPartner($p));
        return new JR($array);
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
        $data = $req->get("partner");
        if (!$data) return new JR("No data", Response::HTTP_BAD_REQUEST);

        $partner = FormService::upsertPartner($em, $data);
        if (is_string($partner)) return new JR($partner, Response::HTTP_BAD_REQUEST);

        return new JR(NS::getPartner($partner), Response::HTTP_CREATED);
    }

    /**
     * @Route("/{partnerId}", name="partner-show", methods={"GET"})
     *
     * @param EntityManagerInterface $em
     * @param $partnerId
     * @return Response
     */
    public function show(EntityManagerInterface $em, $partnerId)
    {
        $partner = $em->getRepository(Partner::class)->find($partnerId);
        if (!$partner) return new JR("Partner not found", Response::HTTP_NOT_FOUND);
        return new JR(NS::getPartner($partner));
    }

    /**
     * @Route("/{partnerId}", name="partner-update", methods={"PUT"})
     *
     * @param Request $req
     * @param EntityManagerInterface $em
     * @param $partnerId
     * @return JR
     */
    public function update(Request $req, EntityManagerInterface $em, $partnerId)
    {
        $data = $req->get("partner");
        if (!$data) return new JR("No data", Response::HTTP_BAD_REQUEST);

        $partner = $em->getRepository(Partner::class)->find($partnerId);
        if (!$partner) return new JR("Partner not found", Response::HTTP_NOT_FOUND);

        $partner = FormService::upsertPartner($em, $data, $partner);
        if (is_string($partner)) return new JR($partner, Response::HTTP_BAD_REQUEST);

        return new JR(NS::getPartner($partner));
    }

    /**
     * @Route("/{partnerId}", name="partner-delete", methods={"DELETE"})
     *
     * @param EntityManagerInterface $em
     * @param $partnerId
     * @return JR
     */
    public function delete(EntityManagerInterface $em, $partnerId)
    {
        $partner = $em->getRepository(Partner::class)->find($partnerId);
        if (!$partner) return new JR("Partner not found", Response::HTTP_NOT_FOUND);

        $em->remove($partner);
        $em->flush();
        return new JR("Partner was deleted", Response::HTTP_NO_CONTENT);
    }
}
