<?php

namespace DP\MachineBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * MachineRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class MachineRepository extends EntityRepository
{
    public function getAvailableMachines() {
        $qb = $this->createQueryBuilder('machine');
        $qb->select('machine.id, machine.privateIp, machine.port, machine.user');
        $qb->where('machine.privateKeyFilename IS NOT NULL');
        return $qb;
        
        $result = $qb->getQuery()->getResult();
        $machines = array();
        
        /*foreach ($result AS $machine) {
            $machines[$machine['id']] = $machine;
        }*/
        
        return $machines;
    }
}