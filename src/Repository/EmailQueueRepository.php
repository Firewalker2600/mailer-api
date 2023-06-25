<?php

namespace App\Repository;

use App\Entity\EmailQueue;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EmailQueue>
 *
 * @method EmailQueue|null find($id, $lockMode = null, $lockVersion = null)
 * @method EmailQueue|null findOneBy(array $criteria, array $orderBy = null)
 * @method EmailQueue[]    findAll()
 * @method EmailQueue[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EmailQueueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EmailQueue::class);
    }

    public function save(EmailQueue $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(EmailQueue $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return EmailQueue[] Returns an array of EmailQueue objects
     */
   public function findUnsentMessages(): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.sent = :val')
            ->setParameter('val', false)
            ->andWhere('e.dispatch_at < :now')
            ->setParameter('now', new \DateTime())
            ->orderBy('e.dispatch_at', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

}
