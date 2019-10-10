<?php

namespace App\Controller;

use App\Entity\Loan;
use App\Service\FormService;
use App\Service\NormalizerService as NS;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse as JR;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/loan")
 */
class LoanController extends AbstractController implements TokenAuthenticatedController
{
    /**
     * @Route("", name="loan-index", methods={"GET"})
     *
     * @param EntityManagerInterface $em
     * @return JR
     */
    public function index(EntityManagerInterface $em)
    {
        $loans = $em->getRepository(Loan::class)->findAll();
        $array = [];
        foreach ($loans as $p) array_push($array, NS::getLoan($p));
        return new JR($array);
    }

    /**
     * @Route("", name="loan-create", methods={"POST"})
     *
     * @param Request $req
     * @param EntityManagerInterface $em
     * @return JR
     */
    public function create(Request $req, EntityManagerInterface $em)
    {
        $data = $req->get("loan");
        if (!$data) return new JR("No data", Response::HTTP_BAD_REQUEST);

        $loan = FormService::upsertLoan($em, $data);
        if (is_string($loan)) return new JR($loan, Response::HTTP_BAD_REQUEST);

        return new JR(NS::getLoan($loan), Response::HTTP_CREATED);
    }

    /**
     * @Route("/{loanId}", name="loan-update", methods={"PUT"})
     *
     * @param Request $req
     * @param EntityManagerInterface $em
     * @param $loanId
     * @return JR
     */
    public function update(Request $req, EntityManagerInterface $em, $loanId)
    {
        $loan = $em->getRepository(Loan::class)->find($loanId);
        if (!$loan) return new JR("Loan not found", Response::HTTP_NOT_FOUND);

        $loan = FormService::upsertLoan($em, [], $loan);
        if (is_string($loan)) return new JR($loan, Response::HTTP_BAD_REQUEST);

        return new JR(NS::getLoan($loan));
    }

    /**
     * @Route("/{loanId}", name="loan-delete", methods={"DELETE"})
     *
     * @param EntityManagerInterface $em
     * @param $loanId
     * @return JR
     */
    public function delete(EntityManagerInterface $em, $loanId)
    {
        $loan = $em->getRepository(Loan::class)->find($loanId);
        if (!$loan) return new JR("Loan not found", Response::HTTP_NOT_FOUND);

        $em->remove($loan);
        $em->flush();
        return new JR("Loan was deleted", Response::HTTP_NO_CONTENT);
    }
}
