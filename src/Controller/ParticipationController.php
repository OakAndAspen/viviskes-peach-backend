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
     */
    public function index(EntityManagerInterface $em)
    {
        $participations = $em->getRepository(Participation::class)->findAll();
        $data = [];
        foreach ($participations as $p) array_push($data, NS::getParticipation($p));
        return new JR($data);
    }

    /**
     * @Route("/", name="participation-upsert", methods={"PUT"})
     */
    public function upsert(Request $req, EntityManagerInterface $em, $participationId)
    {
        $participation = $em->getRepository(Participation::class)->find($participationId);
        if (!$participation) return new JR("Participation not found", Response::HTTP_NOT_FOUND);

        $participation = FormService::upsertParticipation($em, [], $participation);
        if (is_string($participation)) return new JR($participation, Response::HTTP_BAD_REQUEST);

        return new JR(NS::getParticipation($participation));
    }
}
