<?php

namespace App\Service;

use App\Entity\Book;
use App\Entity\Loan;
use App\Entity\Partner;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;

class JsonService
{
    private $rootDir;

    public function __construct($rootDir)
    {
        $this->rootDir = $rootDir;
    }

    public static function getPartner(Partner $p)
    {
        $data = [
            'id' => $p->getId(),
            'label' => $p->getLabel(),
            'url' => $p->getUrl()
        ];
        return $data;
    }

    public static function getBook(Book $b, EntityManagerInterface $em)
    {
        $data = [
            'id' => $b->getId(),
            'name' => $b->getName()
        ];

        $loan = $em->getRepository(Loan::class)->findOneBy([
            "book" => $b,
            "end" => null
        ]);

        if ($loan) $data['loan'] = [
            'user' => [
                'id' => $loan->getUser()->getId(),
                'fullName' => $loan->getUser()->getFirstName() . ' ' . $loan->getUser()->getLastName()
            ],
            'start' => $loan->getStart()->format('Y-m-d')
        ];

        return $data;
    }
}