<?php

namespace App\Controller;

use App\Entity\Participation;
use App\Service\FormService;
use App\Service\NormalizerService as NS;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse as JR;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/participation")
 */
class ParticipationController extends AbstractController implements TokenAuthenticatedController
{
    /**
     * @Route("", name="participation-index", methods={"GET"})
     *
     * @param EntityManagerInterface $em
     * @return JR
     */
    public function index(EntityManagerInterface $em)
    {
        $participations = $em->getRepository(Participation::class)->findAll();
        $data = [];
        foreach ($participations as $p) array_push($data, NS::getParticipation($p));
        return new JR($data);
    }

    /**
     * @Route("", name="participation-create", methods={"POST"})
     *
     * @param Request $req
     * @param EntityManagerInterface $em
     * @return JR
     */
    public function create(Request $req, EntityManagerInterface $em)
    {
        $data = $req->get("participation");
        if (!$data) return new JR("No data", Response::HTTP_BAD_REQUEST);

        $participation = FormService::upsertParticipation($em, $data);
        if (is_string($participation)) return new JR($participation, Response::HTTP_BAD_REQUEST);

        return new JR(NS::getParticipation($participation), Response::HTTP_CREATED);
    }

    /**
     * @Route("/{participationId}", name="participation-update", methods={"PUT"})
     *
     * @param Request $req
     * @param EntityManagerInterface $em
     * @param $participationId
     * @return JR
     */
    public function update(Request $req, EntityManagerInterface $em, $participationId)
    {
        $b = $em->getRepository(Participation::class)->find($participationId);
        if (!$b) return new JR("Participation not found", Response::HTTP_NOT_FOUND);

        $participation = FormService::upsertParticipation($em, []);
        if (is_string($participation)) return new JR($participation, Response::HTTP_BAD_REQUEST);

        return new JR(NS::getParticipation($b));
    }
}
