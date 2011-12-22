<?php

namespace DP\JeuBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * JeuRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class JeuRepository extends EntityRepository
{
    public function getAvailableGames()
    {
        $qb = $this->createQueryBuilder('jeu');
        $qb->select('jeu.id, jeu.name')->where('jeu.available = true');
        
        return $qb->getQuery()->getResult();
    }
}