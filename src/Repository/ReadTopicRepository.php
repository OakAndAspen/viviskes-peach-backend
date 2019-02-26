<?php

namespace App\Repository;

use App\Entity\ReadTopic;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method ReadTopic|null find($id, $lockMode = null, $lockVersion = null)
 * @method ReadTopic|null findOneBy(array $criteria, array $orderBy = null)
 * @method ReadTopic[]    findAll()
 * @method ReadTopic[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ReadTopicRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, ReadTopic::class);
    }

    // /**
    //  * @return ReadTopic[] Returns an array of ReadTopic objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('r.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?ReadTopic
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
