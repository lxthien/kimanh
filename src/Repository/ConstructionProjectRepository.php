<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;

class ConstructionProjectRepository extends EntityRepository
{
    public function findRecentProjects($limit = 20)
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.updatedAt', 'DESC')
            ->addOrderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
